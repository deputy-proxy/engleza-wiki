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
        if (app()->environment('production')) {
            $this->call(ProductionAdminSeeder::class);

            return;
        }

        $this->call(DevelopmentUsersSeeder::class);

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

        $admin = User::query()->where('role', 'admin')->orderBy('id')->firstOrFail();
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
                    'content' => $this->resourceContent(Str::slug($title), $excerpt),
                    'type' => str_contains(strtolower($title), 'worksheet') ? 'worksheet' : (str_contains(strtolower($title), 'lesson plan') ? 'lesson_plan' : 'article'),
                    'status' => $i < 4 ? 'published' : 'draft', 'level_id' => $L($level), 'author_id' => $admin->id,
                    'featured' => $i < 4,
                    'published_at' => now()->subDays(20 - $i),
                    'seo_title' => $title.' — engleza.wiki',
                    'seo_description' => $excerpt,
                    'canonical_url' => null,
                    'robots_index' => $i < 4,
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
        $this->call(RomanianTranslationsSeeder::class);
    }

    private function resourceContent(string $slug, string $excerpt): string
    {
        return match ($slug) {
            'understanding-the-present-simple' => <<<'CONTENT'
# Present Simple: form and use

## Learning objective
By the end of this lesson, you can describe routines, repeated actions and facts, and form affirmative, negative and question sentences in the Present Simple.

## When to use it
Use the Present Simple for:
- habits and routines: *I walk to work every morning.*
- facts and general truths: *Water boils at 100°C at sea level.*
- states and preferences: *She knows the answer. We like jazz.*
- fixed timetables: *The train leaves at 8:15.*

## How to form it

| Sentence | I / you / we / they | he / she / it |
|---|---|---|
| Affirmative | work | works |
| Negative | do not (don't) work | does not (doesn't) work |
| Question | Do ... work? | Does ... work? |

In affirmative sentences, add **-s** to most verbs after *he, she* and *it*. Add **-es** after endings such as *-s, -sh, -ch, -x* and *-o*: *watches, fixes, goes*. A consonant + *y* changes to *-ies*: *study → studies*. A vowel + *y* keeps the *y*: *play → plays*.

For negatives and questions, use **does** for he/she/it. The main verb returns to its base form: *Does she work?* not *Does she works?*

## Worked examples
- *Mara teaches English.* The subject is third-person singular, so *teach* becomes *teaches*.
- *They don't live near the station.* Use *do not* + base verb for a plural subject.
- *Does your brother drive to work?* Use *does* and keep *drive* in the base form.
- *The shop opens at nine.* A regular opening time is a timetable or routine.

## Common mistakes
- Incorrect: *He work every day.* Correct: *He works every day.*
- Incorrect: *She doesn't likes tea.* Correct: *She doesn't like tea.*
- Incorrect: *Do he speak English?* Correct: *Does he speak English?*
- Do not use the Present Simple for an action happening right now when the Present Continuous is required: *I am speaking now.*

## Practice
Choose the correct form.
1. My sister ___ (study) biology.
2. We ___ (not/watch) television during dinner.
3. ___ your parents ___ (live) nearby?
4. The first lesson ___ (start) at 9:00.

## Answer key and explanations
1. **studies** — *my sister* is third-person singular; consonant + *y* changes to *-ies*.
2. **don't watch** — use *don't* with *we* and the base verb *watch*.
3. **Do ... live** — use *do* with the plural subject *your parents*.
4. **starts** — *the first lesson* is singular, so add *-s*.
CONTENT,
            'past-simple-explained' => <<<'CONTENT'
# Past Simple: completed actions in the past

## Learning objective
Use the Past Simple to describe completed past events, form negatives and questions, and distinguish regular from irregular verb forms.

## When to use it
Use the Past Simple for an action or situation completed at a finished time in the past. Common time expressions include *yesterday, last week, in 2022, two days ago* and *when I was a child*.

## Affirmative forms
For most regular verbs, add **-ed**: *work → worked, clean → cleaned*. If a verb ends in consonant + *y*, change *y* to *-ied*: *study → studied*. Some short consonant-vowel-consonant verbs double the final consonant: *stop → stopped*.

Irregular verbs have their own past forms and must be learned: *go → went, see → saw, buy → bought, have → had*.

The verb *be* has two past forms: **was** with *I/he/she/it* and **were** with *you/we/they*.

## Negatives and questions
Use **did not (didn't) + base verb** for all subjects:
- *I didn't see the message.*
- *They didn't go by bus.*

Use **Did + subject + base verb?**:
- *Did you call Anna?*
- *Where did he buy the book?*

After *did* or *didn't*, do not use the past form of the main verb. The past meaning is already carried by *did*.

## Worked examples
- *We visited York last Saturday.* The finished time expression *last Saturday* fits the Past Simple.
- *She bought a new laptop yesterday.* *Buy* is irregular; its past form is *bought*.
- *Were you at home at six?* Use *were* with *you*.

## Common mistakes
- Incorrect: *Did you went home?* Correct: *Did you go home?*
- Incorrect: *He didn't studied.* Correct: *He didn't study.*
- Incorrect: *I were tired.* Correct: *I was tired.*
- Do not add *-ed* to an irregular verb: *buyed* is incorrect; use *bought*.

## Practice
Complete each sentence.
1. We ___ (take) the early train yesterday.
2. Leo ___ (not/finish) his homework last night.
3. ___ you ___ (see) the new exhibition?
4. I ___ (be) ill on Monday, but my friends ___ (be) fine.

## Answer key and explanations
1. **took** — *take* is irregular.
2. **didn't finish** — after *didn't*, use the base form.
3. **Did ... see** — questions use *did* + subject + base verb.
4. **was; were** — use *was* with *I* and *were* with plural *my friends*.
CONTENT,
            'present-perfect-vs-past-simple' => <<<'CONTENT'
# Present Perfect or Past Simple?

## Learning objective
Choose between the Present Perfect and Past Simple by deciding whether the time period is still open or finished, and whether the sentence focuses on present relevance or a completed past event.

## Form
The Present Perfect is **have/has + past participle**: *I have finished; she has gone*. The Past Simple uses the past form: *I finished; she went*.

## Main difference
Use the **Present Perfect** when the exact time is not stated or the experience/result is connected to the present:
- *I've lost my keys.* The result matters now: I cannot open the door.
- *Have you ever tried sushi?* The question is about life experience, not a particular date.
- *She's worked here for five years.* She still works here.

Use the **Past Simple** with a finished past time or when you describe a completed event in a finished period:
- *I lost my keys yesterday.* The time is finished and specified.
- *We visited Rome in 2021.* 2021 is a finished period.
- *She worked there for five years.* The context presents that period as finished.

Common Present Perfect time expressions include *ever, never, already, yet, just, since* and *for*. Common Past Simple expressions include *yesterday, last month, in 2019* and *two hours ago*.

## Worked contrast
- *Have you seen the new film?* asks whether the experience has happened up to now.
- *Did you see the film on Friday?* asks about a specific finished time.
- *He's lived in Cluj since 2020.* He still lives there.
- *He lived in Cluj from 2015 to 2020.* That period is over.

## Common mistakes
- Incorrect: *I have seen her yesterday.* Correct: *I saw her yesterday.*
- Incorrect: *Did you ever visit London?* In standard experience questions, use *Have you ever visited London?*
- Do not choose a tense from one keyword alone. Check what the speaker means and whether the time period is finished.

## Practice
Choose the more appropriate form.
1. I ___ (finish) the report, so you can read it now.
2. We ___ (meet) in 2018.
3. ___ you ever ___ (eat) Korean food?
4. She ___ (work) here for six months and still works here.

## Answer key and explanations
1. **have finished** — the completed result is relevant now; no finished time is given.
2. **met** — *in 2018* is a finished past time.
3. **Have ... eaten** — asks about experience up to the present.
4. **has worked** — the period continues to now; *for six months* describes duration.
CONTENT,
            'common-english-prepositions' => <<<'CONTENT'
# Common English prepositions: place, time and movement

## Learning objective
Choose common prepositions of place, time and movement in everyday sentences, especially *in, on, at, to, into, across* and *through*.

## Place: in, on and at
- **in** describes an enclosed space or area: *in a room, in a city, in a country*.
- **on** describes a surface or line: *on the table, on the wall, on King Street*.
- **at** identifies a point or specific location: *at the door, at the bus stop, at work*.

Compare: *She is in the school* (inside the building) and *She is at school* (there as a student or for school-related activity).

## Time: in, on and at
- **in** + months, years, seasons and parts of the day: *in June, in 2025, in winter, in the morning*.
- **on** + days and dates: *on Tuesday, on 14 May, on my birthday*.
- **at** + clock times and some fixed expressions: *at 7:30, at noon, at night, at the weekend* (British English).

## Movement
- **to** indicates a destination: *go to the station*.
- **into** indicates movement from outside to inside: *walk into the room*.
- **onto** indicates movement to a surface: *put the bag onto the chair*.
- **across** means from one side to the other: *walk across the bridge*.
- **through** means from one end or side to another within an enclosed space or medium: *walk through the tunnel*.

## Worked examples
- *The keys are on the shelf.* They are resting on a surface.
- *Our meeting starts at 10:00 on Thursday.* Use *at* for the time and *on* for the day.
- *They moved to Berlin in April.* Use *to* for the destination and *in* for the month.
- *The children ran through the tunnel and across the field.* The first movement is inside the tunnel; the second crosses an open area.

## Common mistakes
- Incorrect: *in Monday*. Correct: *on Monday*.
- Incorrect: *at the morning*. Correct: *in the morning*.
- Incorrect: *arrive to the airport*. Use *arrive at the airport*; use *arrive in London* for a city.
- Do not confuse *in* (position) with *into* (movement): *She is in the room* / *She walked into the room*.

## Practice
Complete with a suitable preposition.
1. The appointment is ___ 3:30 ___ Friday.
2. My cousin lives ___ a small village ___ Spain.
3. The cat jumped ___ the box.
4. We walked ___ the bridge to reach the other side.

## Answer key and explanations
1. **at; on** — clock time takes *at*; a day takes *on*.
2. **in; in** — use *in* for a village and a country.
3. **into** — the cat moves from outside to inside the box.
4. **across** — the walk goes from one side of the bridge to the other.
CONTENT,
            default => "# {$slug}\n\n{$excerpt}\n\nThis item has been retained as a draft pending a complete, topic-specific explanation, examples, practice and reviewed answer key. It must not be approved on the basis of this placeholder text.",
        };
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
                        QuestionOption::create(['question_id' => $q->id, 'label' => match ((int) $i) {
                            0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', default => 'A'
                        }, 'value' => $val, 'is_correct' => $correct, 'sort_order' => $i]);
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
