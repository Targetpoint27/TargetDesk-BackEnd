<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            [
                'name' => 'Support Technique',
                'code' => 'SUP',
                'description' => 'Département support technique et assistance',
                'is_active' => true,
            ],
            [
                'name' => 'Service Commercial',
                'code' => 'COM',
                'description' => 'Département commercial et ventes',
                'is_active' => true,
            ],
            [
                'name' => 'Service Client',
                'code' => 'CLI',
                'description' => 'Département service client et relation client',
                'is_active' => true,
            ],
            [
                'name' => 'Comptabilité',
                'code' => 'CPT',
                'description' => 'Département comptabilité et facturation',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}