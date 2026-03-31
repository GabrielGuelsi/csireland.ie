<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['name' => 'English Exam Booking', 'type' => 'english_exam', 'description' => 'Cambridge, IELTS, TOEFL and other English exam bookings for college applications.'],
            ['name' => 'Visa Preparation',     'type' => 'visa_prep',    'description' => 'Support and guidance for student visa applications.'],
            ['name' => 'College Updates',      'type' => 'college_update','description' => 'Updates and communications about college application status.'],
            ['name' => 'Study Materials',      'type' => 'material',     'description' => 'Access to study guides, practice tests and learning resources.'],
        ];

        foreach ($services as $data) {
            \App\Models\Service::firstOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
        }
    }
}
