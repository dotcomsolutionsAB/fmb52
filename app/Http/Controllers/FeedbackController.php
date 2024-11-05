<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeedbackModel;
use App\Models\FeedbackResponseModel;

class FeedbackController extends Controller
{
    //
    // create
    public function register_feedback(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|integer',
            'date' => 'required|date',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'ratings' => 'required|integer|min:1|max:5',
            'attachment' => 'nullable|string' // assuming attachment is a URL or file path
        ]);

        $feedback = FeedbackModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'date' => $request->input('date'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'ratings' => $request->input('ratings'),
            'attachment' => $request->input('attachment')
        ]);

        unset($feedback['id'], $feedback['created_at'], $feedback['updated_at']);

        return $feedback
            ? response()->json(['message' => 'Feedback created successfully!', 'data' => $feedback], 201)
            : response()->json(['message' => 'Failed to create feedback!'], 400);
    }

    // view
    public function view_feedbacks()
    {
        $feedbacks = FeedbackModel::select('jamiat_id', 'family_id', 'date', 'subject', 'message', 'ratings', 'attachment')->get();

        return $feedbacks->isNotEmpty()
            ? response()->json(['message' => 'Feedbacks fetched successfully!', 'data' => $feedbacks], 200)
            : response()->json(['message' => 'No feedbacks found!'], 404);
    }

    // update
    public function update_feedback(Request $request, $id)
    {
        $feedback = FeedbackModel::find($id);

        if (!$feedback) {
            return response()->json(['message' => 'Feedback not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|integer',
            'date' => 'required|date',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'ratings' => 'required|integer|min:1|max:5',
            'attachment' => 'nullable|string'
        ]);

        $update_feedback = $feedback->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'date' => $request->input('date'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'ratings' => $request->input('ratings'),
            'attachment' => $request->input('attachment')
        ]);

        return ($update_feedback == 1)
            ? response()->json(['message' => 'Feedback updated successfully!', 'data' => $update_feedback], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_feedback($id)
    {
        $feedback = FeedbackModel::find($id);

        if (!$feedback) {
            return response()->json(['message' => 'Feedback not found!'], 404);
        }

        $feedback->delete();

        return response()->json(['message' => 'Feedback deleted successfully!'], 200);
    }

    // create
    public function register_feedback_response(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|integer',
            'feedback_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'message' => 'required|string',
            'attachment' => 'nullable|string' // assuming attachment is a URL or file path
        ]);

        $feedback_response = FeedbackResponseModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'feedback_id' => $request->input('feedback_id'),
            'name' => $request->input('name'),
            'date' => $request->input('date'),
            'message' => $request->input('message'),
            'attachment' => $request->input('attachment')
        ]);

        unset($feedback_response['id'], $feedback_response['created_at'], $feedback_response['updated_at']);

        return $feedback_response
            ? response()->json(['message' => 'Feedback response created successfully!', 'data' => $feedback_response], 201)
            : response()->json(['message' => 'Failed to create feedback response!'], 400);
    }

    // view
    public function view_feedback_responses()
    {
        $feedback_responses = FeedbackResponseModel::select('jamiat_id', 'family_id', 'feedback_id', 'name', 'date', 'message', 'attachment')->get();

        return $feedback_responses->isNotEmpty()
            ? response()->json(['message' => 'Feedback responses fetched successfully!', 'data' => $feedback_responses], 200)
            : response()->json(['message' => 'No feedback responses found!'], 404);
    }

    // update
    public function update_feedback_response(Request $request, $id)
    {
        $feedback_response = FeedbackResponseModel::find($id);

        if (!$feedback_response) {
            return response()->json(['message' => 'Feedback response not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|integer',
            'feedback_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'message' => 'required|string',
            'attachment' => 'nullable|string'
        ]);

        $update_feedback_response = $feedback_response->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'feedback_id' => $request->input('feedback_id'),
            'name' => $request->input('name'),
            'date' => $request->input('date'),
            'message' => $request->input('message'),
            'attachment' => $request->input('attachment')
        ]);

        return ($update_feedback_response == 1)
            ? response()->json(['message' => 'Feedback response updated successfully!', 'data' => $update_feedback_response], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_feedback_response($id)
    {
        $feedback_response = FeedbackResponseModel::find($id);

        if (!$feedback_response) {
            return response()->json(['message' => 'Feedback response not found!'], 404);
        }

        $feedback_response->delete();

        return response()->json(['message' => 'Feedback response deleted successfully!'], 200);
    }
}
