<?php

namespace App\Enums;

enum BusinessProspectStatus: string
{
    case New = 'new';
    case Analyzed = 'analyzed';
    case ReadyToContact = 'ready_to_contact';
    case Contacted = 'contacted';
    case WaitingResponse = 'waiting_response';
    case FollowUp = 'follow_up';
    case Meeting = 'meeting';
    case Proposal = 'proposal';
    case Won = 'won';
    case NotInterested = 'not_interested';
    case NotQualified = 'not_qualified';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Analyzed => 'Dianalisis',
            self::ReadyToContact => 'Siap Dihubungi',
            self::Contacted => 'Dihubungi',
            self::WaitingResponse => 'Menunggu Respons',
            self::FollowUp => 'Follow-up',
            self::Meeting => 'Meeting',
            self::Proposal => 'Proposal',
            self::Won => 'Berhasil',
            self::NotInterested => 'Tidak Tertarik',
            self::NotQualified => 'Tidak Layak',
        };
    }
}
