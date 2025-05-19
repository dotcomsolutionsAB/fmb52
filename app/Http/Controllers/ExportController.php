<?php
namespace App\Http\Controllers;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\YearModel;
use App\Models\User;
use App\Models\HubModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Auth;


class ExportController extends Controller
{

public function exportUsersWithHubData(Request $request, $year = 0)
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    $jamiat_id = $user->jamiat_id;

    // Determine the year
    if ($year !== 0) {
        $yearRecord = YearModel::where('jamiat_id', $jamiat_id)->where('id', $year)->first();
        $year = $yearRecord->year ?? date('Y');
    } else {
        $currentYearRecord = YearModel::where('jamiat_id', $jamiat_id)->where('is_current', '1')->first();
        $year = $currentYearRecord->year ?? date('Y');
    }

    // Fetch sub-sector permissions from authenticated user
    $permittedSubSectorIds = $user->sub_sector_access_id ?? [];
    if (!is_array($permittedSubSectorIds)) {
        $permittedSubSectorIds = json_decode($permittedSubSectorIds, true) ?? [];
    }

    // Validate input filters (reuse validation as in your method)
    $request->validate([
        'sector' => 'required|array',
        'sector.*' => ['required', function ($attribute, $value, $fail) {
            if ($value !== 'all' && !is_numeric($value)) {
                $fail("The $attribute field must be an integer or the string 'all'.");
            }
        }],
        'sub_sector' => 'required|array',
        'sub_sector.*' => ['required', function ($attribute, $value, $fail) {
            if ($value !== 'all' && !is_numeric($value)) {
                $fail("The $attribute field must be an integer or the string 'all'.");
            }
        }],
        'thali_status' => 'nullable|in:taking,not_taking,once_a_week,joint,other_centre',
        'hub_status' => 'nullable|in:0,1,2,3',
        'mumeneen_type'=>'nullable|in:HOF,FM'
    ]);

    // Handle "all" sectors and sub-sectors
    $requestedSectors = $request->input('sector', []);
    if (in_array('all', $requestedSectors)) {
        $requestedSectors = DB::table('t_sector')->pluck('id')->toArray();
        $requestedSectors[] = null;
    }

    $requestedSubSectors = $request->input('sub_sector', []);
    if (in_array('all', $requestedSubSectors)) {
        $requestedSubSectors = DB::table('t_sub_sector')
            ->whereIn('sector_id', array_filter($requestedSectors))
            ->pluck('id')
            ->toArray();
        $requestedSubSectors[] = null;
    }

    // Ensure sub-sector filter matches permissions
    $finalSubSectors = array_merge(array_intersect($requestedSubSectors, $permittedSubSectorIds), [null]);

    // Fetch hub data for the year
    $hub_data = HubModel::select('family_id', 'hub_amount', 'paid_amount', 'due_amount', 'thali_status', 'year')
        ->where('jamiat_id', $jamiat_id)
        ->where('year', $year)
        ->get()
        ->keyBy('family_id');

    // Fetch users with filters applied
    $get_all_users = User::select(
        'id', 'name', 'email', 'jamiat_id', 'family_id', 'mobile', 'its', 'hof_its',
        'its_family_id', 'folio_no', 'label', 'mumeneen_type', 'title', 'gender', 'age',
        'building', 'sector_id', 'sub_sector_id', 'status', 'role', 'username', 'photo_id','thali_status'
    )
    ->where('jamiat_id', $jamiat_id)
    ->where('status', 'active')
    ->where('role', 'mumeneen')
    ->when($request->filled('thali_status'), function ($query) use ($request) {
        $query->where('thali_status', $request->input('thali_status'));
    })
     ->when($request->filled('mumeneen_type'), function ($query) use ($request) {
        $query->where('mumeneen_type', $request->input('mumeneen_type'));
    })
    ->where(function ($query) use ($requestedSectors) {
        $query->whereIn('sector_id', $requestedSectors)->orWhereNull('sector_id');
    })
    ->where(function ($query) use ($finalSubSectors) {
        $query->whereIn('sub_sector_id', $finalSubSectors)->orWhereNull('sub_sector_id');
    })
    ->whereIn('family_id', $hub_data->keys()->toArray())
    ->orderByRaw("sub_sector_id IS NULL OR sub_sector_id = ''")
    ->orderBy('sub_sector_id')
    ->orderBy('folio_no')
    ->get();

    if ($get_all_users->isEmpty()) {
        return response()->json(['message' => 'Sorry, no records found!'], 404);
    }

    // Calculate overdue amounts for previous years
    $family_ids = $get_all_users->pluck('family_id')->toArray();
    $previous_years = YearModel::where('jamiat_id', $jamiat_id)
        ->where('year', '<', $year)
        ->pluck('year');

    $overdue_data = HubModel::select('family_id', DB::raw('SUM(due_amount) as overdue'))
        ->whereIn('family_id', $family_ids)
        ->where('jamiat_id', $jamiat_id)
        ->whereIn('year', $previous_years)
        ->groupBy('family_id')
        ->get()
        ->keyBy('family_id');

    // Map hub data and overdue to users
    $users_with_hub_data = $get_all_users->map(function ($user) use ($hub_data, $overdue_data) {
        $hub_record = $hub_data->get($user->family_id);

        $user->hub_amount = $hub_record->hub_amount ?? 0;
        $user->paid_amount = $hub_record->paid_amount ?? 0;
        $user->due_amount = $hub_record->due_amount ?? 0;

        $overdue_record = $overdue_data->get($user->family_id);
        $user->overdue = $overdue_record->overdue ?? 0;

        return $user;
    });

    // Apply filtering for hub_status
    if ($request->has('hub_status')) {
        $hubStatus = $request->input('hub_status');

        $users_with_hub_data = $users_with_hub_data->filter(function ($user) use ($hubStatus) {
            switch ($hubStatus) {
                case 0:
                    return ($user->hub_amount == 0 || trim((string)$user->hub_amount) === 'NA');
                case 1:
                    return is_numeric($user->due_amount) && $user->due_amount > 0;
                case 2:
                    return is_numeric($user->overdue) && $user->overdue > 0;
                default:
                    return true;
            }
        })->values();
    }

    // Prepare data for Excel export
    $exportData = $users_with_hub_data->map(function ($user) {
        return [
            'ID' => $user->id,
            'Name' => $user->name,
            'Email' => $user->email,
            'Family ID' => $user->family_id,
            'Mobile' => $user->mobile,
            'ITS' => $user->its,
            'Sector' => optional($user->sector)->name ?? 'N/A',
            'Sub Sector' => optional($user->subSector)->name ?? 'N/A',
            'Thali Status' => $user->thali_status,
            'Hub Amount' => $user->hub_amount,
            'Paid Amount' => $user->paid_amount,
            'Due Amount' => $user->due_amount,
            'Overdue' => $user->overdue,
        ];
    });

    // Define Export class inline
     $export = new class($exportData) implements FromCollection, WithHeadings {
        protected $data;

        public function __construct($data)
        {
            $this->data = $data;
        }

        public function collection()
        {
            return new Collection($this->data);
        }

        public function headings(): array
        {
            return [
                'ID', 'Name', 'Email', 'Family ID', 'Mobile', 'ITS', 'Sector', 'Sub Sector', 'Thali Status',
                'Hub Amount', 'Paid Amount', 'Due Amount', 'Overdue',
            ];
        }
    };

    $fileName = 'users_with_hub_data_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
    $filePath = 'exports/' . $fileName;  // Path inside storage/app/public

    // Save the file to storage/app/public/exports directory
    Excel::store($export, $filePath, 'public');

    // Generate URL for the saved file (adjust this URL according to your setup)
    $fileUrl = asset('storage/' . $filePath);

    // Return JSON response with URL
    return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'User data exported successfully!',
        'file_url' => $fileUrl,
    ]);
}
}