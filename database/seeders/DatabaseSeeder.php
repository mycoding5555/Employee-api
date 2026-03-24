<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
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

        // Employee data: [name, sex, department]
        $employees = [
            // IT
            ['name' => 'John Doe',       'sex' => 'Male',   'dept' => $it],
            ['name' => 'Jane Smith',      'sex' => 'Female', 'dept' => $it],
            ['name' => 'Tom Harris',      'sex' => 'Male',   'dept' => $it],
            ['name' => 'Lisa Wang',       'sex' => 'Female', 'dept' => $it],
            // HR
            ['name' => 'Bob Wilson',      'sex' => 'Male',   'dept' => $hr],
            ['name' => 'Alice Brown',     'sex' => 'Female', 'dept' => $hr],
            ['name' => 'David Kim',       'sex' => 'Male',   'dept' => $hr],
            // Finance
            ['name' => 'Charlie Lee',     'sex' => 'Male',   'dept' => $finance],
            ['name' => 'Diana Chen',      'sex' => 'Female', 'dept' => $finance],
            ['name' => 'Emma Taylor',     'sex' => 'Female', 'dept' => $finance],
            // Marketing
            ['name' => 'Frank Miller',    'sex' => 'Male',   'dept' => $marketing],
            ['name' => 'Grace Nguyen',    'sex' => 'Female', 'dept' => $marketing],
            ['name' => 'Hannah Scott',    'sex' => 'Female', 'dept' => $marketing],
            // Sales
            ['name' => 'Ivan Petrov',     'sex' => 'Male',   'dept' => $sales],
            ['name' => 'Julia Adams',     'sex' => 'Female', 'dept' => $sales],
            ['name' => 'Kevin Park',      'sex' => 'Male',   'dept' => $sales],
        ];

        foreach ($employees as $data) {
            // Generate a simple colored image as a dummy photo
            $photo = $this->generateDummyPhoto($data['name']);

            Employee::create([
                'name'          => $data['name'],
                'sex'           => $data['sex'],
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
