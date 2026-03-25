<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Notifications\PaymentDueReminderNotification;
use Illuminate\Console\Command;

class CheckPaymentDueDates extends Command
{
    protected $signature   = 'payments:check-due-dates';
    protected $description = 'Send payment due reminders for pay-later orders';

    public function handle(): void
    {
        $this->info('Checking payment due dates...');

        $this->sendTomorrowReminders();
        $this->sendTodayReminders();

        $this->info('Done.');
    }

    // ── Orders due tomorrow → send early reminder ─────────────────────────────
    private function sendTomorrowReminders(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        $items = OrderItem::with(['order.customer', 'product'])
            ->where('payment_status', 'pay_later')
            ->where('status', 'delivered')           // order is complete
            ->whereDate('payment_due_at', $tomorrow)
            ->get();

        foreach ($items as $item) {
            $item->order->customer->notify(
                new PaymentDueReminderNotification($item, 'tomorrow')
            );
            $this->line("  ⏰ Tomorrow reminder → order item #{$item->id}");
        }

        $this->info("Tomorrow reminders sent: {$items->count()}");
    }

    // ── Orders due today → send urgent reminder ───────────────────────────────
    private function sendTodayReminders(): void
    {
        $today = now()->toDateString();

        $items = OrderItem::with(['order.customer', 'product'])
            ->where('payment_status', 'pay_later')
            ->where('status', 'delivered')           // order is complete
            ->whereDate('payment_due_at', $today)
            ->get();

        foreach ($items as $item) {
            $item->order->customer->notify(
                new PaymentDueReminderNotification($item, 'today')
            );
            $this->line("  🚨 Today reminder → order item #{$item->id}");
        }

        $this->info("Today reminders sent: {$items->count()}");
    }
}
