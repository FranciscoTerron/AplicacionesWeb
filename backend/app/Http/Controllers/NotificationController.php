<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;
use App\Services\WebPushSender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Envío de notificaciones push desde el panel (HU-B13).
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly WebPushSender $sender,
    ) {}

    public function index(): View
    {
        return view('admin.notifications.index', [
            'subscriberCount' => $this->firestore->countDocuments(WebPushSender::COLLECTION),
            'configured' => (bool) config('services.webpush.public_key'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500', 'starts_with:/'],
        ], [
            'url.starts_with' => 'La URL debe ser una ruta interna de la tienda (empezar con /).',
        ]);

        if (! config('services.webpush.public_key')) {
            return back()->with('error', 'Las claves VAPID no están configuradas en el servidor.');
        }

        $result = $this->sender->broadcast(
            $validated['title'],
            $validated['body'],
            ($validated['url'] ?? '') ?: '/'
        );

        if ($result['sent'] === 0 && $result['failed'] === 0 && $result['removed'] === 0) {
            return back()->with('error', 'No hay dispositivos suscriptos todavía.');
        }

        $message = "Notificación enviada a {$result['sent']} dispositivo(s).";
        if ($result['removed'] > 0) {
            $message .= " Se limpiaron {$result['removed']} suscripciones vencidas.";
        }
        if ($result['failed'] > 0) {
            $message .= " Fallaron {$result['failed']}.";
        }

        return back()->with('success', $message);
    }
}
