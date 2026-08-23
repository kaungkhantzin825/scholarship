<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Scholarship;
use App\Models\BlogPost;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin User ───────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@scholarhub.com'],
            [
                'name'     => 'Edu Scholar Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // ─── Categories ───────────────────────────────────────────────────────
        $categories = [
            ['name' => 'STEM', 'icon' => '🔬', 'color' => '#4F46E5', 'description' => 'Science, Technology, Engineering & Mathematics'],
            ['name' => 'Business & Economics', 'icon' => '💼', 'color' => '#10B981', 'description' => 'MBA, Finance, Economics scholarships'],
            ['name' => 'Arts & Humanities', 'icon' => '🎨', 'color' => '#F59E0B', 'description' => 'Arts, Literature, Philosophy scholarships'],
            ['name' => 'Medicine & Health', 'icon' => '🏥', 'color' => '#EF4444', 'description' => 'Medical, Nursing, Public Health scholarships'],
            ['name' => 'Law & Political Science', 'icon' => '⚖️', 'color' => '#8B5CF6', 'description' => 'Law, Policy, International Relations'],
            ['name' => 'Engineering', 'icon' => '⚙️', 'color' => '#0EA5E9', 'description' => 'Civil, Mechanical, Electrical Engineering'],
            ['name' => 'Education', 'icon' => '📚', 'color' => '#F97316', 'description' => 'Teaching, Pedagogy, Education scholarships'],
            ['name' => 'Social Sciences', 'icon' => '🌍', 'color' => '#14B8A6', 'description' => 'Sociology, Psychology, Anthropology'],
        ];

        $createdCategories = [];
        foreach ($categories as $i => $cat) {
            $createdCategories[] = Category::firstOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, ['sort_order' => $i + 1, 'is_active' => true])
            );
        }

        // ─── Tags ─────────────────────────────────────────────────────────────
        $tagNames = ['Full Scholarship', 'Partial Funding', 'No IELTS', 'Fully Funded', 'Government',
                     'Private', 'Undergraduate', 'Postgraduate', 'Research', 'Exchange Program'];
        $tags = [];
        foreach ($tagNames as $name) {
            $tags[] = Tag::firstOrCreate(['name' => $name]);
        }

        // ─── Scholarships ─────────────────────────────────────────────────────
        $scholarships = [
            [
                'category_id'  => $createdCategories[0]->id,
                'title'        => 'Google DeepMind Scholarship for Computer Science',
                'excerpt'      => 'Fully funded scholarship for outstanding CS students worldwide. Covers tuition, living expenses, and mentorship.',
                'description'  => '<h2>About the Scholarship</h2><p>The Google DeepMind Scholarship supports exceptional students in Computer Science, AI, and Machine Learning programs at top universities worldwide.</p><h3>What You Get</h3><ul><li>Full tuition coverage</li><li>Monthly living stipend of $2,000</li><li>Mentorship from Google engineers</li><li>Internship opportunity at Google</li></ul>',
                'eligibility'  => '<ul><li>Enrolled in a Master\'s or PhD program in Computer Science or related field</li><li>Minimum GPA of 3.5/4.0</li><li>Strong interest in AI/ML research</li><li>Open to all nationalities</li></ul>',
                'benefits'     => 'Full tuition + $2,000/month stipend + travel allowance',
                'required_documents' => "- Academic transcripts\n- 2 recommendation letters\n- Statement of purpose\n- CV/Resume",
                'amount'       => 50000,
                'amount_type'  => 'full',
                'currency'     => 'USD',
                'country'      => 'USA',
                'level'        => 'master',
                'field_of_study' => 'Computer Science / AI',
                'deadline'     => now()->addMonths(3)->toDateString(),
                'official_link' => 'https://buildyourfuture.withgoogle.com/scholarships',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'category_id'  => $createdCategories[1]->id,
                'title'        => 'Commonwealth Scholarship for Developing Countries',
                'excerpt'      => 'Prestigious scholarship offered by the UK government for students from Commonwealth nations.',
                'description'  => '<h2>Commonwealth Scholarship</h2><p>Funded by the UK Department for International Development, these scholarships are for talented individuals from Commonwealth countries who could not otherwise afford to study in the UK.</p>',
                'eligibility'  => '<ul><li>Citizen of a Commonwealth country</li><li>Hold a first degree at upper second class (2:1) or above</li><li>Committed to development of your home country</li></ul>',
                'benefits'     => 'Approved airfare, tuition fees, living allowance, thesis grant',
                'required_documents' => "- Personal statement\n- Academic transcripts\n- References\n- Development impact statement",
                'amount'       => 30000,
                'amount_type'  => 'full',
                'currency'     => 'GBP',
                'country'      => 'United Kingdom',
                'level'        => 'master',
                'field_of_study' => 'Any',
                'deadline'     => now()->addMonths(2)->toDateString(),
                'official_link' => 'https://cscuk.fcdo.gov.uk/',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'category_id'  => $createdCategories[3]->id,
                'title'        => 'WHO Research Grant for Global Health',
                'excerpt'      => 'Support for researchers working on global health challenges in low-income countries.',
                'description'  => '<h2>WHO Research Grant</h2><p>The World Health Organization offers research grants to support health professionals and researchers from developing countries.</p>',
                'eligibility'  => '<ul><li>Medical degree or equivalent</li><li>Working in a low or middle-income country</li><li>Research proposal related to global health priorities</li></ul>',
                'benefits'     => 'Research funding up to $25,000 + conference travel',
                'required_documents' => "- Research proposal\n- Medical license\n- Institutional support letter",
                'amount'       => 25000,
                'amount_type'  => 'partial',
                'currency'     => 'USD',
                'country'      => 'Switzerland',
                'level'        => 'phd',
                'field_of_study' => 'Medicine / Public Health',
                'deadline'     => now()->addMonths(4)->toDateString(),
                'official_link' => 'https://who.int',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'category_id'  => $createdCategories[5]->id,
                'title'        => 'DAAD Scholarship for Engineering Students',
                'excerpt'      => 'German Academic Exchange Service offers scholarships for engineering students to study in Germany.',
                'description'  => '<h2>DAAD Scholarship</h2><p>DAAD offers scholarships for international students to study engineering in Germany\'s world-class universities.</p>',
                'eligibility'  => '<ul><li>Bachelor\'s degree in Engineering with excellent grades</li><li>German language skills (B2) preferred for some programs</li><li>Under 32 years of age</li></ul>',
                'benefits'     => 'Monthly stipend €934, health insurance, travel allowance',
                'required_documents' => "- Academic transcripts\n- Language certificate\n- Motivation letter\n- 2 academic references",
                'amount'       => 11000,
                'amount_type'  => 'monthly',
                'currency'     => 'EUR',
                'country'      => 'Germany',
                'level'        => 'master',
                'field_of_study' => 'Engineering',
                'deadline'     => now()->addMonths(5)->toDateString(),
                'official_link' => 'https://daad.de',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'category_id'  => $createdCategories[2]->id,
                'title'        => 'Chevening Scholarship for Future Leaders',
                'excerpt'      => 'UK government scholarship for talented emerging leaders from around the world.',
                'description'  => '<h2>Chevening Scholarships</h2><p>Chevening is the UK government\'s international awards programme aimed at developing global leaders.</p>',
                'eligibility'  => '<ul><li>Citizen of a Chevening-eligible country</li><li>At least 2 years work experience</li><li>Undergraduate degree</li><li>Meet English language requirements</li></ul>',
                'benefits'     => 'Tuition fees, monthly stipend, travel costs, arrival allowance',
                'required_documents' => "- Academic transcripts\n- Work experience evidence\n- IELTS/TOEFL score\n- 2 references",
                'amount'       => 45000,
                'amount_type'  => 'full',
                'currency'     => 'GBP',
                'country'      => 'United Kingdom',
                'level'        => 'master',
                'field_of_study' => 'Any',
                'deadline'     => now()->addDays(45)->toDateString(),
                'official_link' => 'https://chevening.org',
                'is_featured'  => true,
                'status'       => 'active',
            ],
        ];

        foreach ($scholarships as $data) {
            $scholarship = Scholarship::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
            // Attach 2-3 random tags
            $scholarship->tags()->sync(
                collect($tags)->random(rand(2, 3))->pluck('id')->toArray()
            );
        }

        // ─── Blog Posts ───────────────────────────────────────────────────────
        $posts = [
            [
                'title'         => 'Top 10 Scholarships You Can Apply for Right Now',
                'excerpt'       => 'A curated list of scholarships with upcoming deadlines that are actively accepting applications.',
                'content'       => '<h2>Don\'t Miss These Opportunities</h2><p>Scholarships can transform your academic journey. Here are the top 10 currently open scholarships...</p>',
                'post_category' => 'news',
                'author_name'   => 'Edu Scholar Editorial',
                'is_featured'   => true,
                'published_at'  => now()->subDays(2),
            ],
            [
                'title'         => 'How to Write a Winning Scholarship Essay',
                'excerpt'       => 'Practical tips from successful scholarship recipients on crafting a compelling personal statement.',
                'content'       => '<h2>The Art of Scholarship Writing</h2><p>Your personal statement is your chance to stand out. Here\'s how to make it unforgettable...</p>',
                'post_category' => 'tips',
                'author_name'   => 'Dr. Sarah Chen',
                'is_featured'   => true,
                'published_at'  => now()->subDays(5),
            ],
            [
                'title'         => 'From Myanmar to Oxford: My Scholarship Journey',
                'excerpt'       => 'A real story of how one student secured a full scholarship to Oxford University.',
                'content'       => '<h2>My Story</h2><p>Three years ago, I was a student in Yangon with big dreams and an empty wallet. Today, I\'m completing my PhD at Oxford...</p>',
                'post_category' => 'success-story',
                'author_name'   => 'Aung Ko Ko',
                'is_featured'   => false,
                'published_at'  => now()->subDays(10),
            ],
            [
                'title'         => 'Complete Guide to IELTS for Scholarship Applications',
                'excerpt'       => 'Everything you need to know about meeting English language requirements for international scholarships.',
                'content'       => '<h2>IELTS Preparation Guide</h2><p>Most international scholarships require proof of English proficiency. Here is your complete preparation guide...</p>',
                'post_category' => 'guide',
                'author_name'   => 'Edu Scholar Team',
                'is_featured'   => false,
                'published_at'  => now()->subDays(15),
            ],
        ];

        foreach ($posts as $data) {
            BlogPost::firstOrCreate(['title' => $data['title']], $data);
        }

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('📧 Admin login: admin@scholarhub.com / password');
    }
}
