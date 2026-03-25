<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Title;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure photos directory exists
        Storage::disk('public')->makeDirectory('photos');

        // Create departments
        $it       = Department::create(['name' => 'IT']);
        $hr       = Department::create(['name' => 'HR']);
        $finance  = Department::create(['name' => 'Finance']);
        $marketing = Department::create(['name' => 'Marketing']);
        $sales    = Department::create(['name' => 'Sales']);

        //crate titles
         $director = Title::create(['name' => 'Director']);
         $manager   = Title::create(['name' => 'Manager']);
         $analyst   = Title::create(['name' => 'Analyst']);   

        // Employee data: [name, sex, department]
        $employees = [
            // IT
            ['name' => 'John Doe',       'sex' => 'Male', 'title' => $director, 'dept' => $it],
            ['name' => 'Jane Smith',      'sex' => 'Female', 'title' => $manager, 'dept' => $it],
            ['name' => 'Tom Harris',      'sex' => 'Male',   'title' => $analyst, 'dept' => $it],
            ['name' => 'Lisa Wang',       'sex' => 'Female', 'title' => $analyst, 'dept' => $it],
            // HR
            ['name' => 'Bob Wilson',      'sex' => 'Male',   'title' => $manager, 'dept' => $hr],
            ['name' => 'Alice Brown',     'sex' => 'Female', 'title' => $analyst, 'dept' => $hr],
            ['name' => 'David Kim',       'sex' => 'Male',   'title' => $analyst, 'dept' => $hr],
            // Finance
            ['name' => 'Charlie Lee',     'sex' => 'Male',   'title' => $manager, 'dept' => $finance],
            ['name' => 'Diana Chen',      'sex' => 'Female', 'title' => $analyst, 'dept' => $finance],
            ['name' => 'Emma Taylor',     'sex' => 'Female', 'title' => $analyst, 'dept' => $finance],
            // Marketing
            ['name' => 'Frank Miller',    'sex' => 'Male',   'title' => $manager, 'dept' => $marketing],
            ['name' => 'Grace Nguyen',    'sex' => 'Female', 'title' => $analyst, 'dept' => $marketing],
            ['name' => 'Hannah Scott',    'sex' => 'Female', 'title' => $analyst, 'dept' => $marketing],
            // Sales
            ['name' => 'Ivan Petrov',     'sex' => 'Male',   'title' => $manager, 'dept' => $sales],
            ['name' => 'Julia Adams',     'sex' => 'Female', 'title' => $analyst, 'dept' => $sales],
            ['name' => 'Kevin Park',      'sex' => 'Male',   'title' => $analyst, 'dept' => $sales],
        ];

        foreach ($employees as $data) {
            // Generate a simple colored image as a dummy photo
            $photo = $this->generateDummyPhoto($data['name']);

            Employee::create([
                'name'          => $data['name'],
                'sex'           => $data['sex'],
                'title_id'      => $data['title']->id,
                'department_id' => $data['dept']->id,
                'photo'         => $photo,
            ]);
        }
    }

    /**
     * Generate a simple dummy PNG photo with the person's initials.
     */
    private function generateDummyPhoto(string $name): string
    {
        $width  = 200;
        $height = 200;
        $img    = imagecreatetruecolor($width, $height);

        // Random background color
        $r = rand(60, 200);
        $g = rand(60, 200);
        $b = rand(60, 200);
        $bg    = imagecolorallocate($img, $r, $g, $b);
        $white = imagecolorallocate($img, 255, 255, 255);

        imagefilledrectangle($img, 0, 0, $width, $height, $bg);

        // Draw initials
        $parts    = explode(' ', $name);
        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1] ?? '', 0, 1));
        $fontSize = 5; // built-in font size (1-5)
        $fontW    = imagefontwidth($fontSize) * strlen($initials);
        $fontH    = imagefontheight($fontSize);
        imagestring($img, $fontSize, ($width - $fontW) / 2, ($height - $fontH) / 2, $initials, $white);

        // Save to storage
        $filename = 'photos/' . str_replace(' ', '_', strtolower($name)) . '.png';
        $fullPath = Storage::disk('public')->path($filename);
        imagepng($img, $fullPath);
        imagedestroy($img);

        return $filename;
    }
}
