<?php

namespace App\Console\Commands;

use App\Services\AccountService;
use Illuminate\Console\Command;

class PurgeDeletedAccountsCommand extends Command
{
    protected $signature = 'accounts:purge-deleted';

    protected $description = 'Hard-delete accounts whose deletion grace window has elapsed';

    public function handle(AccountService $accounts): int
    {
        $purged = $accounts->purgeDue();

        $this->info("Purged {$purged} account(s).");

        return self::SUCCESS;
    }
}
