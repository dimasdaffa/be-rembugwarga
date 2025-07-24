<?php

namespace App\Listeners;

use App\Events\AnnouncementCreated;
use App\Models\User;
use App\Notifications\NewAnnouncement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification; 

class SendNewAnnouncementNotification
{
    public function __construct()
    {
        //
    }

    public function handle(AnnouncementCreated $event): void
    {
        // 1. Ambil semua user yang perannya 'warga'
        $warga = User::where('role', 'warga')->get();

        // 2. Kirim notifikasi ke semua warga tersebut
        if ($warga->isNotEmpty()) {
            Notification::send($warga, new NewAnnouncement($event->announcement));
        }
    }
}
