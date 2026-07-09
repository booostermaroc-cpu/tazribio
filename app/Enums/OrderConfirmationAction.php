<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum OrderConfirmationAction: string
{
    use HasColorAndLabel;

    case WhatsappContact = 'whatsapp_contact';
    case PhoneCall = 'phone_call';
    case SmsContact = 'sms_contact';
    case OrderConfirmed = 'order_confirmed';
    case ConfirmedViaWhatsapp = 'confirmed_via_whatsapp';
    case ConfirmedViaCall = 'confirmed_via_call';
    case RefusalNoAnswer = 'refusal_no_answer';
    case ContactBusy = 'contact_busy';
    case ContactVoicemail = 'contact_voicemail';
    case RefusalClientRefuses = 'refusal_client_refuses';
    case RefusalWrongNumber = 'refusal_wrong_number';
    case RefusalCallbackLater = 'refusal_callback_later';
    case ReviewLinkSent = 'review_link_sent';
    case OrderPrepared = 'order_prepared';
    case OrderShipped = 'order_shipped';


    public function color(): string
    {
        return match ($this) {
            self::WhatsappContact, self::ConfirmedViaWhatsapp => 'success',
            self::PhoneCall, self::ConfirmedViaCall => 'info',
            self::SmsContact => 'gray',
            self::OrderConfirmed => 'primary',
            self::RefusalNoAnswer, self::ContactBusy, self::ContactVoicemail, self::RefusalCallbackLater => 'warning',
            self::RefusalClientRefuses, self::RefusalWrongNumber => 'danger',
            self::ReviewLinkSent => 'warning',
            self::OrderPrepared => 'info',
            self::OrderShipped => 'warning',
        };
    }

    public static function fromRefusalReason(string $reason): self
    {
        return match ($reason) {
            'client_refuses', 'cancelled' => self::RefusalClientRefuses,
            'wrong_number' => self::RefusalWrongNumber,
            'callback_later' => self::RefusalCallbackLater,
            'busy' => self::ContactBusy,
            'voicemail' => self::ContactVoicemail,
            default => self::RefusalNoAnswer,
        };
    }

    /** @return list<self> */
    public static function processSteps(): array
    {
        return [
            self::WhatsappContact,
            self::PhoneCall,
            self::OrderConfirmed,
        ];
    }

    /** @return list<self> */
    public static function trackedClickActions(): array
    {
        return [
            self::WhatsappContact,
            self::PhoneCall,
            self::SmsContact,
            self::ConfirmedViaWhatsapp,
            self::ConfirmedViaCall,
            self::RefusalNoAnswer,
            self::ContactBusy,
            self::ContactVoicemail,
            self::RefusalWrongNumber,
            self::RefusalClientRefuses,
            self::RefusalCallbackLater,
            self::OrderPrepared,
            self::OrderShipped,
            self::ReviewLinkSent,
        ];
    }

    /** @return list<self> */
    public static function refusalActions(): array
    {
        return [
            self::RefusalNoAnswer,
            self::ContactBusy,
            self::ContactVoicemail,
            self::RefusalWrongNumber,
            self::RefusalClientRefuses,
        ];
    }

    public function targetOrderStatus(): ?OrderStatus
    {
        return match ($this) {
            self::ConfirmedViaWhatsapp, self::ConfirmedViaCall, self::OrderConfirmed => OrderStatus::Confirmed,
            self::RefusalNoAnswer => OrderStatus::NoAnswer,
            self::ContactBusy => OrderStatus::Busy,
            self::ContactVoicemail => OrderStatus::Voicemail,
            self::RefusalWrongNumber => OrderStatus::WrongNumber,
            self::RefusalClientRefuses => OrderStatus::Cancelled,
            self::SmsContact => OrderStatus::SmsSent,
            default => null,
        };
    }
}
