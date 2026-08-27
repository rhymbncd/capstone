<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = Section::all();

        User::factory()
            ->count(100)
            ->sequence(fn (Sequence $sequence) => [
                'section_id' => $sections->isNotEmpty()
                    ? $sections[$sequence->index % $sections->count()]->id
                    : null,
            ])
            ->create();
    }
}
