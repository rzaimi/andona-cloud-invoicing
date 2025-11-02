<?php

namespace App\Traits;

use App\Models\EmailLog;

trait LogsEmails
{
    /**
     * Log an email that was sent
     */
    protected function logEmail(
        string $companyId,
        string $recipientEmail,
        string $subject,
        string $type,
        ?string $customerId = null,
        ?string $recipientName = null,
        ?string $body = null,
        ?string $relatedType = null,
        ?string $relatedId = null,
        ?array $metadata = null,
        string $status = 'sent',
        ?string $errorMessage = null
    ): EmailLog {
        return EmailLog::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'body' => $body,
            'type' => $type,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'sent_at' => now(),
        ]);
    }
}


