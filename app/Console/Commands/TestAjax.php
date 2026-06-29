<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TestAjax extends Command
{
    protected $signature = 'test:ajax';
    protected $description = 'Test Ajax';

    public function handle()
    {
        $user = User::first();
        Auth::login($user);

        $request = Request::create('/production/products/data', 'GET', [
            'draw' => 1,
            'columns' => [
                ['data' => 'sku', 'name' => 'sku', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'full_name', 'name' => 'full_name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'unit', 'name' => 'unit', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'warehouse', 'name' => 'defaultWarehouse.name', 'searchable' => 'true', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'bom_count', 'name' => 'boms_count', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'is_active', 'name' => 'is_active', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'action', 'name' => '', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']]
            ],
            'order' => [
                ['column' => 0, 'dir' => 'desc']
            ],
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false']
        ]);

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);

        $this->info("STATUS CODE: " . $response->getStatusCode());
        
        $content = $response->getContent();
        if ($response->getStatusCode() === 500) {
            // It might be a Laravel error page, let's look for exception message
            if (preg_match('/<title>(.*?)<\/title>/', $content, $matches)) {
                $this->error("Title: " . $matches[1]);
            }
            if (preg_match('/class="exception_message">(.*?)<\//', $content, $matches)) {
                $this->error("Message: " . $matches[1]);
            } else {
                $this->line(substr(strip_tags($content), 0, 500));
            }
        } else {
            $this->line(substr($content, 0, 200));
        }
    }
}
