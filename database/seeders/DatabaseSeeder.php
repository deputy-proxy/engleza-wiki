<?php

namespace Database\Seeders;

use App\Models\Audience;
use App\Models\Category;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::updateOrCreate(['email' => 'admin@engleza.wiki'], ['name' => 'Admin', 'password' => bcrypt('password'), 'role' => 'admin', 'email_verified_at' => now()]);
        User::updateOrCreate(['email' => 'editor@engleza.wiki'], ['name' => 'Editor', 'password' => bcrypt('password'), 'role' => 'editor', 'email_verified_at' => now()]);
        User::updateOrCreate(['email' => 'teacher@engleza.wiki'], ['name' => 'Teacher', 'password' => bcrypt('password'), 'role' => 'teacher', 'email_verified_at' => now()]);
        User::updateOrCreate(['email' => 'learner@engleza.wiki'], ['name' => 'Learner', 'password' => bcrypt('password'), 'role' => 'learner', 'email_verified_at' => now()]);

        // Levels
        foreach (['Pre-A1', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $i => $n) {
            Level::updateOrCreate(['slug' => Str::slug($n)], ['name' => $n, 'sort_order' => $i, 'description' => "CEFR level $n"]);
        }
        // Skills
        foreach (['Grammar', 'Vocabulary', 'Reading', 'Writing', 'Listening', 'Speaking', 'Pronunciation', 'Use of English'] as $n) {
            Skill::updateOrCreate(['slug' => Str::slug($n)], ['name' => $n]);
        }
        // Audiences
        foreach (['Learners', 'Teachers', 'Parents', 'Students', 'Schools', 'Adults', 'Children'] as $n) {
            Audience::updateOrCreate(['slug' => Str::slug($n)], ['name' => $n]);
        }
        // Tags
        foreach (['past simple', 'present simple', 'grammar', 'tenses', 'vocabulary', 'travel', 'work', 'prepositions', 'conditionals', 'phrasal verbs', 'worksheet', 'beginner', 'intermediate', 'advanced'] as $n) {
            Tag::updateOrCreate(['slug' => Str::slug($n)], ['name' => $n]);
        }
        // Categories
        $catData = [
            ['Grammar', ['Tenses', 'Conditionals', 'Modal Verbs', 'Articles']],
            ['Vocabulary', ['Food', 'Travel', 'Work', 'Education']],
            ['Listening', []],
            ['Speaking', []],
            ['Writing', []],
            ['Reading', []],
            ['Lesson Plans', []],
        ];
        foreach ($catData as [$root,$kids]) {
            $c = Category::updateOrCreate(['slug' => Str::slug($root)], ['name' => $root, 'description' => "$root resources"]);
            foreach ($kids as $k) {
                Category::updateOrCreate(['slug' => Str::slug($k)], ['name' => $k, 'parent_id' => $c->id]);
            }
        }
        // Topics
        $grammarRoot = Topic::updateOrCreate(['slug' => 'grammar'], ['name' => 'Grammar']);
        foreach (['Present Simple', 'Present Continuous', 'Past Simple', 'Past Continuous', 'Present Perfect', 'Past Perfect', 'Future Forms', 'Modal Verbs', 'Conditionals', 'Articles', 'Prepositions', 'Reported Speech', 'Passive Voice', 'Vocabulary', 'Reading'] as $n) {
            Topic::updateOrCreate(['slug' => Str::slug($n)], ['name' => $n, 'parent_id' => $grammarRoot->id]);
        }

        $admin = User::where('email', 'admin@engleza.wiki')->first();
        $L = fn ($s) => Level::where('slug', $s)->first()?->id;
        $S = fn ($s) => Skill::where('slug', Str::slug($s))->first();
        $T = fn ($s) => Topic::where('slug', Str::slug($s))->first();
        $C = fn ($s) => Category::where('slug', Str::slug($s))->first();
        $A = fn ($s) => Audience::where('slug', Str::slug($s))->first();
        $Tag = fn ($s) => Tag::where('slug', Str::slug($s))->first();

        // Resources
        $resources = [
            ['Understanding the Present Simple', 'a1', 'Present Simple', 'Grammar', 'Learn how to use the present simple tense for habits, routines, and general truths.', 'present simple'],
            ['Past Simple Explained', 'a2', 'Past Simple', 'Grammar', 'Master the past simple tense to talk about finished actions in the past.', 'past simple'],
            ['Present Perfect vs Past Simple', 'b1', 'Present Perfect', 'Grammar', 'Understand the difference between present perfect and past simple.', 'tenses'],
            ['Common English Prepositions', 'a2', 'Prepositions', 'Grammar', 'A practical guide to common English prepositions of place, time and movement.', 'prepositions'],
            ['50 Useful English Phrasal Verbs', 'b1', 'Vocabulary', 'Vocabulary', 'Fifty essential English phrasal verbs with meanings, examples and practice.', 'phrasal verbs'],
            ['English Vocabulary for Travel', 'a2', 'Vocabulary', 'Vocabulary', 'Travel-related English vocabulary for airports, hotels, restaurants and directions.', 'travel'],
            ['English Vocabulary for Work', 'b1', 'Vocabulary', 'Vocabulary', 'Essential English vocabulary for the workplace, meetings and emails.', 'work'],
            ['How to Improve Your Listening Skills', 'b1', 'Vocabulary', 'Listening', 'Practical tips and strategies to improve your English listening skills.', 'intermediate'],
            ['How to Practise Speaking English Alone', 'a2', 'Vocabulary', 'Speaking', 'Techniques to practise English speaking on your own — no partner required.', 'intermediate'],
            ['Common English Mistakes', 'a2', 'Vocabulary', 'Grammar', 'Common English mistakes learners make and how to avoid them.', 'grammar'],
            ['A2 Grammar Practice', 'a2', 'Vocabulary', 'Grammar', 'Grammar exercises for A2 elementary learners with answer key.', 'beginner'],
            ['B1 Grammar Practice', 'b1', 'Vocabulary', 'Grammar', 'Grammar exercises for B1 intermediate learners with answers.', 'intermediate'],
            ['English Conversation Topics', 'b1', 'Vocabulary', 'Speaking', 'Fifty English conversation topics for classroom or self-study practice.', 'intermediate'],
            ['Writing Better Emails in English', 'b2', 'Vocabulary', 'Writing', 'How to write clear and professional emails in English.', 'advanced'],
            ['How to Learn English Vocabulary', 'a2', 'Vocabulary', 'Vocabulary', 'Effective strategies for building English vocabulary.', 'beginner'],
            ['English Classroom Games', 'a2', 'Vocabulary', 'Lesson Plans', 'Fun English games to use in the classroom for teachers.', 'beginner'],
            ['Past Simple Worksheet', 'a2', 'Past Simple', 'Grammar', 'Printable past simple worksheet with exercises and answer key.', 'worksheet'],
            ['Present Perfect Worksheet', 'b1', 'Present Perfect', 'Grammar', 'Printable present perfect worksheet with exercises.', 'worksheet'],
            ['B1 Vocabulary Worksheet', 'b1', 'Vocabulary', 'Vocabulary', 'B1-level vocabulary worksheet with matching and gap-fill.', 'worksheet'],
            ['English Lesson Plan: Daily Routines', 'a1', 'Present Simple', 'Lesson Plans', 'A ready-to-use English lesson plan on daily routines for A1 learners.', 'beginner'],
        ];
        foreach ($resources as $i => [$title,$level,$topic,$cat,$excerpt,$tag]) {
            $r = Resource::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title, 'excerpt' => $excerpt,
                    'content' => "## $title\n\n$excerpt\n\nThis resource is part of engleza.wiki's free English learning collection. It provides clear explanations, practical examples, and useful exercises to help you improve.\n\n### Key points\n- Concept clearly explained\n- Multiple examples\n- Practice exercises\n- Answer key\n\n### Example\n\nShe **works** in a hospital. They **don't play** football on Sundays. Where **do you live**?\n\n### When to use it\n\nUse this pattern for habits, routines, facts, and general truths.",
                    'type' => str_contains(strtolower($title), 'worksheet') ? 'worksheet' : (str_contains(strtolower($title), 'lesson plan') ? 'lesson_plan' : 'article'),
                    'status' => 'published', 'level_id' => $L($level), 'author_id' => $admin->id,
                    'featured' => $i < 4,
                    'published_at' => now()->subDays(20 - $i),
                    'seo_title' => $title.' — engleza.wiki',
                    'seo_description' => $excerpt,
                ]
            );
            $skill = $S(str_contains($cat, 'Grammar') ? 'Grammar' : (str_contains($cat, 'Vocab') ? 'Vocabulary' : (str_contains($cat, 'Listening') ? 'Listening' : (str_contains($cat, 'Speaking') ? 'Speaking' : (str_contains($cat, 'Writing') ? 'Writing' : (str_contains($cat, 'Reading') ? 'Reading' : 'Grammar'))))));
            $r->skills()->sync([$skill->id]);
            if ($t = $T($topic)) {
                $r->topics()->sync([$t->id]);
            }
            if ($c = $C($cat)) {
                $r->categories()->sync([$c->id]);
            }
            $aud = [$A('Learners')->id];
            if (str_contains(strtolower($title), 'worksheet') || str_contains(strtolower($title), 'lesson plan') || str_contains(strtolower($title), 'classroom')) {
                $aud[] = $A('Teachers')->id;
            }
            $r->audiences()->sync($aud);
            $tagIds = array_filter([$Tag($tag)?->id, $Tag('grammar')?->id]);
            $r->tags()->sync(array_unique($tagIds));
        }

        // Tests
        $this->seedTests($L, $S, $T);

        // Interactive resources (cards, listening, reading, writing)
        $this->call(InteractiveResourcesSeeder::class);
    }

    private function seedTests($L, $S, $T): void
    {
        $testsData = [
            ['English Level Test – A1', 'a1', 'Test your English at A1 beginner level.', 15],
            ['English Level Test – A2', 'a2', 'Test your English at A2 elementary level.', 20],
            ['English Level Test – B1', 'b1', 'Test your English at B1 intermediate level.', 25],
            ['English Level Test – B2', 'b2', 'Test your English at B2 upper-intermediate level.', 30],
            ['Grammar Test – Present Simple', 'a1', 'Focused test on the present simple tense.', 10],
            ['Grammar Test – Past Simple', 'a2', 'Focused test on the past simple tense.', 10],
            ['Vocabulary Test – Everyday English', 'a2', 'Everyday English vocabulary test.', 12],
        ];

        $qBank = $this->questionBank();

        foreach ($testsData as [$title,$level,$desc,$duration]) {
            $test = Test::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title, 'description' => $desc, 'level_id' => $L($level), 'skill_id' => $S('Grammar')->id,
                'duration_minutes' => $duration, 'passing_score' => 60, 'access_type' => 'free', 'status' => 'published',
                'is_public' => true, 'shuffle_questions' => false, 'show_correct_answers' => true,
                'instructions' => "Answer all questions to the best of your ability. You have {$duration} minutes to complete this test. No dictionaries allowed.",
                'published_at' => now(),
            ]);
            // Reset sections
            $test->sections()->delete();

            $sectionsBySkill = str_contains($title, 'Level Test')
                ? ['Grammar' => 10, 'Vocabulary' => 6, 'Reading' => 4]
                : (str_contains($title, 'Vocabulary') ? ['Vocabulary' => 8] : ['Grammar' => 8]);

            $order = 0;
            foreach ($sectionsBySkill as $sname => $count) {
                $sec = TestSection::create(['test_id' => $test->id, 'title' => $sname, 'sort_order' => $order++, 'description' => "$sname section"]);
                $pool = collect($qBank[$sname] ?? $qBank['Grammar'])->shuffle()->take($count);
                $qo = 0;
                foreach ($pool as $qd) {
                    $q = Question::create([
                        'test_section_id' => $sec->id, 'type' => 'single_choice', 'question' => $qd['q'],
                        'explanation' => $qd['exp'] ?? null, 'points' => 1, 'difficulty' => 'medium', 'sort_order' => $qo++,
                    ]);
                    foreach ($qd['opts'] as $i => [$val,$correct]) {
                        QuestionOption::create(['question_id' => $q->id, 'label' => chr(65 + $i), 'value' => $val, 'is_correct' => $correct, 'sort_order' => $i]);
                    }
                    if (! empty($qd['topic']) && ($t = $T($qd['topic']))) {
                        $q->topics()->sync([$t->id]);
                    }
                }
            }
        }
    }

    private function questionBank(): array
    {
        return [
            'Grammar' => [
                ['q' => 'She ___ to school every day.', 'opts' => [['go', false], ['goes', true], ['going', false], ['gone', false]], 'exp' => 'Third person singular takes -s in the present simple.', 'topic' => 'Present Simple'],
                ['q' => 'They ___ football on Sundays.', 'opts' => [['plays', false], ['play', true], ['played', false], ['playing', false]], 'topic' => 'Present Simple'],
                ['q' => 'I ___ to London last summer.', 'opts' => [['go', false], ['goed', false], ['went', true], ['gone', false]], 'exp' => 'Past simple of "go" is "went".', 'topic' => 'Past Simple'],
                ['q' => 'He ___ his homework yesterday.', 'opts' => [['do', false], ['does', false], ['did', true], ['done', false]], 'topic' => 'Past Simple'],
                ['q' => 'I ___ never ___ to Japan.', 'opts' => [['have / been', true], ['has / been', false], ['am / going', false], ['was / been', false]], 'topic' => 'Present Perfect'],
                ['q' => 'If it rains, we ___ at home.', 'opts' => [['stay', true], ['stayed', false], ['will stayed', false], ['staying', false]], 'topic' => 'Conditionals'],
                ['q' => 'She is ___ tallest girl in the class.', 'opts' => [['a', false], ['the', true], ['—', false], ['an', false]], 'topic' => 'Articles'],
                ['q' => 'The book is ___ the table.', 'opts' => [['in', false], ['at', false], ['on', true], ['by', false]], 'topic' => 'Prepositions'],
                ['q' => 'You ___ smoke here — it\'s forbidden.', 'opts' => [['can', false], ['must not', true], ['should', false], ['would', false]], 'topic' => 'Modal Verbs'],
                ['q' => 'She said she ___ tired.', 'opts' => [['is', false], ['was', true], ['are', false], ['were', false]], 'topic' => 'Reported Speech'],
                ['q' => 'The letter ___ by Anna.', 'opts' => [['wrote', false], ['was written', true], ['is writing', false], ['writes', false]], 'topic' => 'Passive Voice'],
                ['q' => 'While I ___ dinner, the phone rang.', 'opts' => [['cook', false], ['cooked', false], ['was cooking', true], ['am cooking', false]], 'topic' => 'Past Continuous'],
                ['q' => 'By 8pm they ___ already ___ .', 'opts' => [['have / left', false], ['had / left', true], ['are / leaving', false], ['were / left', false]], 'topic' => 'Past Perfect'],
            ],
            'Vocabulary' => [
                ['q' => 'What is the opposite of "cheap"?', 'opts' => [['big', false], ['expensive', true], ['long', false], ['bright', false]], 'topic' => 'Vocabulary'],
                ['q' => 'A person who writes books is called a ___ .', 'opts' => [['painter', false], ['author', true], ['doctor', false], ['pilot', false]], 'topic' => 'Vocabulary'],
                ['q' => 'You use this to unlock a door.', 'opts' => [['key', true], ['fork', false], ['comb', false], ['pen', false]], 'topic' => 'Vocabulary'],
                ['q' => 'Which word means "very tired"?', 'opts' => [['exhausted', true], ['excited', false], ['exciting', false], ['expensive', false]], 'topic' => 'Vocabulary'],
                ['q' => 'Choose the phrasal verb: I need to ___ my French.', 'opts' => [['brush up on', true], ['brush down', false], ['brush over', false], ['brush around', false]], 'topic' => 'Vocabulary'],
                ['q' => '"Give up" means ___ .', 'opts' => [['continue', false], ['stop trying', true], ['succeed', false], ['start again', false]], 'topic' => 'Vocabulary'],
                ['q' => 'At the airport you show your ___ .', 'opts' => [['ticket', false], ['passport', true], ['keys', false], ['license', false]], 'topic' => 'Vocabulary'],
                ['q' => 'A job interview is usually with a ___ .', 'opts' => [['patient', false], ['recruiter', true], ['tenant', false], ['landlord', false]], 'topic' => 'Vocabulary'],
            ],
            'Reading' => [
                ['q' => 'Read: "Anna wakes up at 6am and goes for a run." What time does Anna wake up?', 'opts' => [['5am', false], ['6am', true], ['7am', false], ['8am', false]], 'topic' => 'Reading'],
                ['q' => 'Read: "The library closes at 8pm on weekdays and at 5pm on weekends." When does it close on Sunday?', 'opts' => [['8pm', false], ['6pm', false], ['5pm', true], ['7pm', false]], 'topic' => 'Reading'],
                ['q' => 'Read: "Tom cannot come to the meeting because he is sick." Why is Tom absent?', 'opts' => [['He is busy', false], ['He is sick', true], ['He is late', false], ['He is angry', false]], 'topic' => 'Reading'],
                ['q' => 'Read: "The museum is free on Wednesdays." Which day is free?', 'opts' => [['Monday', false], ['Wednesday', true], ['Friday', false], ['Sunday', false]], 'topic' => 'Reading'],
            ],
        ];
    }
}
