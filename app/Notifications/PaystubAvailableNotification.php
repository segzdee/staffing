<?php

namespace App\Notifications;

use App\Models\PayrollRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PaystubAvailableNotification - FIN-005: Payroll Processing System
 *
 * Sent to workers when their paystub is available after payroll processing.
 */
class PaystubAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected PayrollRun $payrollRun
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Get worker's specific amount from the payroll items
        $workerItems = $this->payrollRun->items()
            ->where('user_id', $notifiable->id)
            ->get();

        $netAmount = $workerItems->sum('net_amount');
        $grossAmount = $workerItems->sum('gross_amount');

        return (new MailMessage)
            ->subject('Your Paystub is Ready: '.$this->payrollRun->reference)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Great news! Your paystub is now available.')
            ->line('**Pay Period:** '.$this->payrollRun->period_start->format('M d, Y').' - '.$this->payrollRun->period_end->format('M d, Y'))
            ->line('**Gross Earnings:** $'.number_format($grossAmount, 2))
            ->line('**Net Pay:** $'.number_format($netAmount, 2))
            ->line('Your payment has been processed and should arrive in your account shortly.')
            ->action('View Paystub', route('worker.paystubs.show', $this->payrollRun))
            ->line('Thank you for your hard work!')
            ->salutation('Best regards,'."\n".config('app.name'));
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Get worker's specific amount from the payroll items
        $workerItems = $this->payrollRun->items()
            ->where('user_id', $notifiable->id)
            ->get();

        $netAmount = $workerItems->sum('net_amount');

        return [
            'type' => 'paystub_available',
            'payroll_run_id' => $this->payrollRun->id,
            'reference' => $this->payrollRun->reference,
            'period_start' => $this->payrollRun->period_start->toDateString(),
            'period_end' => $this->payrollRun->period_end->toDateString(),
            'pay_date' => $this->payrollRun->pay_date->toDateString(),
            'net_amount' => $netAmount,
            'message' => "Your paystub for {$this->payrollRun->reference} is now available. Net pay: $".number_format($netAmount, 2),
            'action_url' => route('worker.paystubs.show', $this->payrollRun),
        ];
    }
}
