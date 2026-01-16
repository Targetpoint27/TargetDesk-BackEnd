<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'Entreprise ACME',
                'entreprise',
                'contact@acme.com',
                '0123456789',
                '123 Rue de la Paix, 75001 Paris',
                '12345678901234',
                'Technologie',
                'https://acme.com',
                'Client important'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'type',
            'email',
            'phone',
            'address',
            'siret',
            'sector',
            'website',
            'notes'
        ];
    }
}