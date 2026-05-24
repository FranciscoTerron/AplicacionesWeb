<?php

namespace App\Exports;

use App\Services\FirestoreService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
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

        return collect($this->fetchAllDocumentsFast('clients'))->map(function ($doc) {
            return [
                $doc['id'] ?? '',
                $doc['name'] ?? '',
                $doc['email'] ?? '',
                $doc['phone'] ?? '',
                ($doc['active'] ?? true) ? 'Activo' : 'Inactivo',
                $doc['created_at'] ? Carbon::parse($doc['created_at'])->format('d/m/Y H:i:s') : '',
                $doc['updated_at'] ? Carbon::parse($doc['updated_at'])->format('d/m/Y H:i:s') : '',
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'Email', 'Teléfono', 'Estado', 'Creado', 'Actualizado'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => 'FFE2E8F0'],
            ],
        ]);

        $sheet->getStyle('D2:D'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function fetchAllDocumentsFast(string $collection): array
    {
        $allDocuments = [];
        $batchSize = 1000;
        $maxIterations = 50;
        $iteration = 0;
        $cursor = null;

        do {
            $result = $this->firestore->listDocuments($collection, $batchSize, $cursor, 'created_at');

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
