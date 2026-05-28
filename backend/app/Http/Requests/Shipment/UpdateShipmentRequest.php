<?php

namespace App\Http\Requests\Shipment;

use App\Models\Shipment;
use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|string',
            'address' => 'required|string|max:500',
            'tracking_code' => 'nullable|string|max:100',
            'carrier' => 'nullable|string|max:100',
            'status' => 'required|in:'.implode(',', array_keys(Shipment::statuses())),
            'shipped_at' => 'nullable|date',
            'delivered_at' => 'nullable|date|after_or_equal:shipped_at',
            'notes' => 'nullable|string|max:1000',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'La orden asociada es obligatoria.',
            'address.required' => 'La dirección de envío es obligatoria.',
            'status.required' => 'El estado del envío es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'delivered_at.after_or_equal' => 'La fecha de entrega no puede ser anterior a la fecha de despacho.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $orderId = $this->input('order_id');
            if ($orderId) {
                $order = app(FirestoreService::class)->getDocument('orders', $orderId);
                if (! $order) {
                    $validator->errors()->add('order_id', 'La orden seleccionada no existe.');
                }
            }

            $shipmentId = $this->route('shipment');
            if ($shipmentId) {
                $shipment = app(FirestoreService::class)->getDocument('shipments', $shipmentId);
                $currentStatus = $shipment['status'] ?? null;
                if ($currentStatus === Shipment::STATUS_DELIVERED && $this->input('status') !== Shipment::STATUS_DELIVERED) {
                    $validator->errors()->add('status', 'No se puede cambiar el estado de un envío que ya fue entregado.');
                }
            }
        });
    }
}
