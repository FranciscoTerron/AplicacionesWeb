<?php

namespace App\Exports;

use App\Services\FirestoreService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    protected FirestoreService $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function collection()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        return collect($this->fetchAllDocumentsFast('orders'))->map(function ($doc) {
            return [
                $doc['id'] ?? '',
                $doc['number'] ?? '',
                $doc['client_id'] ?? '',
                $doc['total'] ?? '',
                $doc['status'] ?? '',
                $doc['payment_method'] ?? '',
                $doc['created_at'] ? Carbon::parse($doc['created_at'])->format('d/m/Y H:i:s') : '',
                $doc['updated_at'] ? Carbon::parse($doc['updated_at'])->format('d/m/Y H:i:s') : '',
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'Número', 'Cliente ID', 'Total', 'Estado', 'Forma de Pago', 'Creado', 'Actualizado'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => 'FFE2E8F0'],
            ],
        ]);
    }

    protected function fetchAllDocumentsFast(string $collection): array
    {
        $allDocuments = [];
        $batchSize = 1000;
        $maxIterations = 50;
        $iteration = 0;
        $cursor = null;

        do {
            $result = $this->firestore->listDocuments($collection, $batchSize, $cursor, 'number');

            if (! isset($result['documents']) || ! is_array($result['documents']) || count($result['documents']) === 0) {
                break;
            }

            $documents = $result['documents'];
            $allDocuments = array_merge($allDocuments, $documents);

            $iteration++;
            $hasMore = $result['hasMore'] ?? false;
            if ($hasMore) {
                $lastDoc = end($documents);
                $cursor = $lastDoc['id'] ?? null;
            } else {
                break;
            }
        } while ($iteration < $maxIterations);

        return $allDocuments;
    }
}
