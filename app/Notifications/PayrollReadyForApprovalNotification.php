<?php

namespace App\Notifications;

use App\Models\PayrollRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PayrollReadyForApprovalNotification - FIN-005: Payroll Processing System
 *
 * Sent to admins when a payroll run is submitted for approval.
 */
class PayrollReadyForApprovalNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Payroll Ready for Approval: '.$this->payrollRun->reference)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new payroll run has been submitted for your approval.')
            ->line('**Reference:** '.$this->payrollRun->reference)
            ->line('**Period:** '.$this->payrollRun->period_start->format('M d, Y').' - '.$this->payrollRun->period_end->format('M d, Y'))
            ->line('**Pay Date:** '.$this->payrollRun->pay_date->format('M d, Y'))
            ->line('**Total Workers:** '.$this->payrollRun->total_workers)
            ->line('**Gross Amount:** $'.number_format($this->payrollRun->gross_amount, 2))
            ->line('**Net Amount:** $'.number_format($this->payrollRun->net_amount, 2))
            ->action('Review Payroll', route('admin.payroll.show', $this->payrollRun))
            ->line('Please review and approve this payroll run before the pay date.')
            ->salutation('Best regards,'."\n".config('app.name'));
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payroll_approval_required',
            'payroll_run_id' => $this->payrollRun->id,
            'reference' => $this->payrollRun->reference,
            'period_start' => $this->payrollRun->period_start->toDateString(),
            'period_end' => $this->payrollRun->period_end->toDateString(),
            'pay_date' => $this->payrollRun->pay_date->toDateString(),
            'total_workers' => $this->payrollRun->total_workers,
            'gross_amount' => $this->payrollRun->gross_amount,
            'net_amount' => $this->payrollRun->net_amount,
            'message' => "Payroll {$this->payrollRun->reference} is ready for approval",
            'action_url' => route('admin.payroll.show', $this->payrollRun),
        ];
    }
}
