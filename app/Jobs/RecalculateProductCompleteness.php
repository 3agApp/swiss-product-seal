<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Template;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateProductCompleteness implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $templateId,
    ) {}

    public function handle(): void
    {
        $template = Template::find($this->templateId);

        if (! $template) {
            return;
        }

        $template->products()
            ->cursor()
            ->each(function (Product $product): void {
                $product->refreshCompletenessScore();
            });
    }
}
