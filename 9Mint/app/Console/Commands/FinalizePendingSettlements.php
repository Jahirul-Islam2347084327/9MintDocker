<?php

namespace App\Console\Commands;

use App\Services\OrderFinalizationService;
use Illuminate\Console\Command;

class FinalizePendingSettlements extends Command
{
    protected $signature = 'settlements:finalize';

    protected $description = 'Finalize held NFT settlements whose release time has passed';

    public function handle(OrderFinalizationService $orderFinalization): int
    {
        $count = $orderFinalization->finalizeMaturedSettlements();
        $this->info("Finalized settlements: {$count}");

        return self::SUCCESS;
    }
}
