<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Semester;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Semester::create(['name' => 'Spring 2026', 'is_current' => false]);
        Semester::create(['name' => 'Summer 2026', 'is_current' => true]);
        Semester::create(['name' => 'Fall 2026', 'is_current' => false]);
    }
}
