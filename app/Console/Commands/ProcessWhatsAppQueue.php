<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessWhatsAppQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-queue';
    protected $description = 'Process WhatsApp message queue';

    public function handle()
    {
        $controller = new WhatsAppQueueController();
        $controller->processQueue();
        $this->info('WhatsApp queue processed successfully.');
    }
   
    /**
     * The console command description.
     *
     * @var string
     */
   

    /**
     * Execute the console command.
     */
    
}
