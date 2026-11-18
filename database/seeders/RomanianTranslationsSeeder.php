<?php

namespace Database\Seeders;

use App\Models\Audience;
use App\Models\CardResource;
use App\Models\Category;
use App\Models\ContentTranslation;
use App\Models\Level;
use App\Models\ListeningResource;
use App\Models\Question;
use App\Models\ReadingResource;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\WritingResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class RomanianTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->translateSlugs(Resource::class, [
            'understanding-the-present-simple' => ['title' => 'Înțelegerea timpului Present Simple', 'excerpt' => 'Află cum se folosește timpul Present Simple pentru obiceiuri, rutine și adevăruri generale.'],
            'past-simple-explained' => ['title' => 'Timpul Past Simple explicat', 'excerpt' => 'Învață să folosești timpul Past Simple pentru acțiuni încheiate în trecut.'],
            'present-perfect-vs-past-simple' => ['title' => 'Present Perfect sau Past Simple', 'excerpt' => 'Înțelege diferența dintre timpurile Present Perfect și Past Simple.'],
            'common-english-prepositions' => ['title' => 'Prepoziții uzuale în limba engleză', 'excerpt' => 'Un ghid practic despre prepozițiile uzuale de loc, timp și mișcare din limba engleză.'],
            '50-useful-english-phrasal-verbs' => ['title' => '50 de verbe frazale utile în engleză', 'excerpt' => 'Cincizeci de verbe frazale esențiale, cu sensuri, exemple și exerciții.'],
            'english-vocabulary-for-travel' => ['title' => 'Vocabular englezesc pentru călătorii', 'excerpt' => 'Vocabular în engleză pentru aeroporturi, hoteluri, restaurante și indicații de orientare.'],
            'english-vocabulary-for-work' => ['title' => 'Vocabular englezesc pentru serviciu', 'excerpt' => 'Vocabular esențial în engleză pentru mediul profesional, ședințe și e-mailuri.'],
            'how-to-improve-your-listening-skills' => ['title' => 'Cum îți poți îmbunătăți înțelegerea limbii engleze vorbite', 'excerpt' => 'Sfaturi și strategii practice pentru a-ți îmbunătăți înțelegerea limbii engleze vorbite.'],
            'how-to-practise-speaking-english-alone' => ['title' => 'Cum poți exersa vorbirea în engleză de unul singur', 'excerpt' => 'Tehnici prin care poți exersa vorbirea în engleză pe cont propriu, fără un partener de conversație.'],
            'common-english-mistakes' => ['title' => 'Greșeli frecvente în limba engleză', 'excerpt' => 'Greșeli frecvente pe care le fac cei care învață engleza și cum le poți evita.'],
            'a2-grammar-practice' => ['title' => 'Exerciții de gramatică pentru nivelul A2', 'excerpt' => 'Exerciții de gramatică pentru nivelul elementar A2, însoțite de răspunsuri.'],
            'b1-grammar-practice' => ['title' => 'Exerciții de gramatică pentru nivelul B1', 'excerpt' => 'Exerciții de gramatică pentru nivelul intermediar B1, cu răspunsuri.'],
            'english-conversation-topics' => ['title' => 'Subiecte pentru conversații în limba engleză', 'excerpt' => 'Cincizeci de subiecte pentru exersarea conversației în engleză, la clasă sau individual.'],
            'writing-better-emails-in-english' => ['title' => 'Cum să scrii e-mailuri mai bune în engleză', 'excerpt' => 'Cum să redactezi e-mailuri clare și profesioniste în limba engleză.'],
            'how-to-learn-english-vocabulary' => ['title' => 'Cum să înveți vocabularul limbii engleze', 'excerpt' => 'Strategii eficiente pentru dezvoltarea vocabularului în limba engleză.'],
            'english-classroom-games' => ['title' => 'Jocuri pentru orele de limba engleză', 'excerpt' => 'Jocuri distractive pe care profesorii le pot folosi la orele de limba engleză.'],
            'past-simple-worksheet' => ['title' => 'Fișă de lucru: Past Simple', 'excerpt' => 'Fișă de lucru imprimabilă despre Past Simple, cu exerciții și răspunsuri.'],
            'present-perfect-worksheet' => ['title' => 'Fișă de lucru: Present Perfect', 'excerpt' => 'Fișă de lucru imprimabilă despre Present Perfect, cu exerciții.'],
            'b1-vocabulary-worksheet' => ['title' => 'Fișă de vocabular pentru nivelul B1', 'excerpt' => 'Fișă de vocabular pentru nivelul B1, cu exerciții de asociere și completare.'],
            'english-lesson-plan-daily-routines' => ['title' => 'Plan de lecție: Rutina zilnică', 'excerpt' => 'Un plan de lecție gata de folosit, despre rutina zilnică, pentru cursanții de nivel A1.'],
            'everyday-english-vocabulary-flashcards' => ['title' => 'Carduri de vocabular pentru engleza de zi cu zi', 'excerpt' => '15 carduri pentru dezvoltarea vocabularului uzual în engleză: de la definiție la cuvânt.'],
            'english-irregular-verbs-flashcards' => ['title' => 'Carduri cu verbe neregulate în engleză', 'excerpt' => '15 verbe neregulate esențiale: infinitiv, Past Simple și participiul trecut.'],
            'basic-english-grammar-qa-flashcards' => ['title' => 'Carduri cu întrebări și răspunsuri de gramatică engleză', 'excerpt' => '10 întrebări și răspunsuri de gramatică pentru recapitulare rapidă.'],
            'a2-listening-at-the-restaurant' => ['title' => 'Ascultare A2: La restaurant', 'excerpt' => 'Exersează engleza folosită la restaurant: cum comanzi mâncare și întrebi despre meniu.'],
            'b1-listening-a-job-interview' => ['title' => 'Ascultare B1: Un interviu de angajare', 'excerpt' => 'Înțelege un interviu de angajare obișnuit, cu întrebări despre experiență și motivație.'],
            'a2-reading-a-day-in-london' => ['title' => 'Lectură A2: O zi în Londra', 'excerpt' => 'Un text scurt despre o zi obișnuită petrecută explorând Londra.'],
            'b1-reading-why-learning-languages-matters' => ['title' => 'Lectură B1: De ce este important să învățăm limbi străine', 'excerpt' => 'Un articol scurt despre beneficiile învățării unei limbi străine.'],
            'b2-reading-the-future-of-remote-work' => ['title' => 'Lectură B2: Viitorul muncii la distanță', 'excerpt' => 'Un text analitic despre schimbările din lumea muncii la distanță.'],
            'a2-writing-describe-your-daily-routine' => ['title' => 'Scriere A2: Descrie-ți rutina zilnică', 'excerpt' => 'Un exercițiu scurt de scriere despre o zi obișnuită.'],
            'a2-writing-write-an-email-to-a-friend' => ['title' => 'Scriere A2: Scrie un e-mail unui prieten', 'excerpt' => 'Exercițiu de redactare a unui e-mail informal.'],
            'b1-writing-describe-your-last-holiday' => ['title' => 'Scriere B1: Descrie ultima ta vacanță', 'excerpt' => 'Exersează timpurile trecutului: descrie o vacanță memorabilă.'],
            'b2-writing-should-students-use-smartphones-at-school' => ['title' => 'Scriere B2: Ar trebui elevii să folosească telefoane la școală?', 'excerpt' => 'Un eseu argumentativ despre folosirea telefoanelor inteligente în școli.'],
        ]);

        $this->translateResourceBodies();
        $this->translateResourceMetadata();

        $this->translateSlugs(Test::class, [
            'english-level-test-a1' => ['title' => 'Test de nivel la limba engleză – A1', 'description' => 'Evaluează-ți cunoștințele de engleză la nivelul începător A1.'],
            'english-level-test-a2' => ['title' => 'Test de nivel la limba engleză – A2', 'description' => 'Evaluează-ți cunoștințele de engleză la nivelul elementar A2.'],
            'english-level-test-b1' => ['title' => 'Test de nivel la limba engleză – B1', 'description' => 'Evaluează-ți cunoștințele de engleză la nivelul intermediar B1.'],
            'english-level-test-b2' => ['title' => 'Test de nivel la limba engleză – B2', 'description' => 'Evaluează-ți cunoștințele de engleză la nivelul intermediar superior B2.'],
            'grammar-test-present-simple' => ['title' => 'Test de gramatică – Present Simple', 'description' => 'Test dedicat timpului Present Simple.'],
            'grammar-test-past-simple' => ['title' => 'Test de gramatică – Past Simple', 'description' => 'Test dedicat timpului Past Simple.'],
            'vocabulary-test-everyday-english' => ['title' => 'Test de vocabular – Engleza de zi cu zi', 'description' => 'Test de vocabular uzual în limba engleză.'],
        ], [
            'instructions' => [
                'Answer all questions to the best of your ability. You have 10 minutes to complete this test. No dictionaries allowed.' => 'Răspunde cât poți de bine la toate întrebările. Ai la dispoziție 10 minute pentru a finaliza testul. Nu este permisă folosirea dicționarelor.',
                'Answer all questions to the best of your ability. You have 12 minutes to complete this test. No dictionaries allowed.' => 'Răspunde cât poți de bine la toate întrebările. Ai la dispoziție 12 minute pentru a finaliza testul. Nu este permisă folosirea dicționarelor.',
                'Answer all questions to the best of your ability. You have 15 minutes to complete this test. No dictionaries allowed.' => 'Răspunde cât poți de bine la toate întrebările. Ai la dispoziție 15 minute pentru a finaliza testul. Nu este permisă folosirea dicționarelor.',
                'Answer all questions to the best of your ability. You have 20 minutes to complete this test. No dictionaries allowed.' => 'Răspunde cât poți de bine la toate întrebările. Ai la dispoziție 20 de minute pentru a finaliza testul. Nu este permisă folosirea dicționarelor.',
                'Answer all questions to the best of your ability. You have 25 minutes to complete this test. No dictionaries allowed.' => 'Răspunde cât poți de bine la toate întrebările. Ai la dispoziție 25 de minute pentru a finaliza testul. Nu este permisă folosirea dicționarelor.',
                'Answer all questions to the best of your ability. You have 30 minutes to complete this test. No dictionaries allowed.' => 'Răspunde cât poți de bine la toate întrebările. Ai la dispoziție 30 de minute pentru a finaliza testul. Nu este permisă folosirea dicționarelor.',
            ],
        ]);

        $this->translateNames(Audience::class, [
            'Learners' => 'Cursanți', 'Teachers' => 'Profesori', 'Parents' => 'Părinți',
            'Students' => 'Elevi și studenți', 'Schools' => 'Școli', 'Adults' => 'Adulți', 'Children' => 'Copii',
        ]);

        $this->translateNames(Category::class, [
            'Grammar' => 'Gramatică', 'Tenses' => 'Timpuri verbale', 'Conditionals' => 'Condiționale',
            'Modal Verbs' => 'Verbe modale', 'Articles' => 'Articole', 'Vocabulary' => 'Vocabular',
            'Food' => 'Mâncare', 'Travel' => 'Călătorii', 'Work' => 'Serviciu', 'Education' => 'Educație',
            'Listening' => 'Ascultare', 'Speaking' => 'Vorbire', 'Writing' => 'Scriere',
            'Reading' => 'Citire', 'Lesson Plans' => 'Planuri de lecție',
        ], ['description' => [
            'Grammar resources' => 'Resurse de gramatică.',
            'Vocabulary resources' => 'Resurse pentru dezvoltarea vocabularului.',
            'Listening resources' => 'Resurse pentru exersarea înțelegerii limbii engleze vorbite.',
            'Speaking resources' => 'Resurse pentru exersarea vorbirii în limba engleză.',
            'Writing resources' => 'Resurse pentru exersarea scrierii în limba engleză.',
            'Reading resources' => 'Resurse pentru exersarea citirii în limba engleză.',
            'Lesson Plans resources' => 'Planuri de lecție pentru predarea limbii engleze.',
        ]]);

        $this->translateNames(Topic::class, [
            'Grammar' => 'Gramatică', 'Present Simple' => 'Present Simple', 'Present Continuous' => 'Present Continuous',
            'Past Simple' => 'Past Simple', 'Past Continuous' => 'Past Continuous', 'Present Perfect' => 'Present Perfect',
            'Past Perfect' => 'Past Perfect', 'Future Forms' => 'Forme de viitor', 'Modal Verbs' => 'Verbe modale',
            'Conditionals' => 'Construcții condiționale', 'Articles' => 'Articole', 'Prepositions' => 'Prepoziții',
            'Reported Speech' => 'Vorbire indirectă', 'Passive Voice' => 'Diateza pasivă',
            'Vocabulary' => 'Vocabular', 'Reading' => 'Citire',
        ]);

        $this->translateNames(Level::class, [
            'Pre-A1' => 'Pre-A1 · Începător', 'A1' => 'A1 · Începător', 'A2' => 'A2 · Elementar',
            'B1' => 'B1 · Intermediar', 'B2' => 'B2 · Intermediar superior',
            'C1' => 'C1 · Avansat', 'C2' => 'C2 · Stăpânire avansată',
        ], ['description' => [
            'CEFR level Pre-A1' => 'Nivel Pre-A1 conform Cadrului european comun de referință pentru limbi.',
            'CEFR level A1' => 'Nivel A1 conform Cadrului european comun de referință pentru limbi.',
            'CEFR level A2' => 'Nivel A2 conform Cadrului european comun de referință pentru limbi.',
            'CEFR level B1' => 'Nivel B1 conform Cadrului european comun de referință pentru limbi.',
            'CEFR level B2' => 'Nivel B2 conform Cadrului european comun de referință pentru limbi.',
            'CEFR level C1' => 'Nivel C1 conform Cadrului european comun de referință pentru limbi.',
            'CEFR level C2' => 'Nivel C2 conform Cadrului european comun de referință pentru limbi.',
        ]]);

        $this->translateNames(Skill::class, [
            'Grammar' => 'Gramatică', 'Vocabulary' => 'Vocabular', 'Reading' => 'Citire',
            'Writing' => 'Scriere', 'Listening' => 'Ascultare', 'Speaking' => 'Vorbire',
            'Pronunciation' => 'Pronunție', 'Use of English' => 'Utilizarea limbii engleze',
        ]);

        $this->translateNames(Tag::class, [
            'past simple' => 'Past Simple', 'present simple' => 'Present Simple', 'grammar' => 'gramatică',
            'tenses' => 'timpuri verbale', 'vocabulary' => 'vocabular', 'travel' => 'călătorii',
            'work' => 'serviciu', 'prepositions' => 'prepoziții', 'conditionals' => 'condiționale',
            'phrasal verbs' => 'verbe frazale', 'worksheet' => 'fișă de lucru',
            'beginner' => 'începător', 'intermediate' => 'intermediar', 'advanced' => 'avansat',
        ]);

        $this->translateFields(TestSection::class, [
            'title' => [
                'Grammar' => 'Gramatică', 'Vocabulary' => 'Vocabular', 'Reading' => 'Citire',
                'Listening' => 'Ascultare', 'Use of English' => 'Utilizarea limbii engleze',
            ],
            'description' => [
                'Grammar questions' => 'Întrebări de gramatică.',
                'Vocabulary questions' => 'Întrebări de vocabular.',
                'Reading questions' => 'Întrebări de înțelegere a textului.',
                'Grammar section' => 'Secțiunea de gramatică',
                'Vocabulary section' => 'Secțiunea de vocabular',
                'Reading section' => 'Secțiunea de înțelegere a textului',
            ],
            'instructions' => [
                'Choose the correct answer.' => 'Alege răspunsul corect.',
                'Read the text and answer the questions.' => 'Citește textul și răspunde la întrebări.',
            ],
        ]);

        $this->translateFields(Question::class, [
            'explanation' => [
                'Third person singular takes -s in the present simple.' => 'La persoana a treia singular, verbul primește terminația -s la timpul Present Simple.',
                'Past simple of "go" is "went".' => 'Forma verbului „go” la Past Simple este „went”.',
            ],
        ]);

        $this->translateInteractiveContent();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, array<string, string>>  $translations
     * @param  array<string, array<string, string>>  $defaults
     */
    private function translateSlugs(string $modelClass, array $translations, array $defaults = []): void
    {
        foreach ($translations as $slug => $fields) {
            $model = $modelClass::query()->where('slug', $slug)->first();
            if (! $model) {
                continue;
            }

            foreach ($fields as $field => $value) {
                $this->save($model, $field, $value);
            }

            foreach ($defaults as $field => $fieldTranslations) {
                $source = $model->getAttribute($field);
                if (is_string($source) && isset($fieldTranslations[$source])) {
                    $this->save($model, $field, $fieldTranslations[$source]);
                }
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string>  $names
     * @param  array<string, array<string, string>>  $extra
     */
    private function translateNames(string $modelClass, array $names, array $extra = []): void
    {
        foreach ($modelClass::query()->get() as $model) {
            $name = (string) $model->getAttribute('name');
            if (isset($names[$name])) {
                $this->save($model, 'name', $names[$name]);
            }

            foreach ($extra as $field => $translations) {
                $source = $model->getAttribute($field);
                if (is_string($source) && isset($translations[$source])) {
                    $this->save($model, $field, $translations[$source]);
                }
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, array<string, string>>  $fields
     */
    private function translateFields(string $modelClass, array $fields): void
    {
        foreach ($modelClass::query()->get() as $model) {
            foreach ($fields as $field => $translations) {
                $source = $model->getAttribute($field);
                if (is_string($source) && isset($translations[$source])) {
                    $this->save($model, $field, $translations[$source]);
                }
            }
        }
    }

    private function translateResourceMetadata(): void
    {
        foreach (Resource::query()->where('status', 'published')->get() as $resource) {
            $title = (string) $resource->translated('title', 'ro');
            $excerpt = (string) $resource->translated('excerpt', 'ro');
            $this->save($resource, 'seo_title', $title);
            $this->save($resource, 'seo_description', $excerpt);
            $this->save($resource, 'og_title', $title);
            $this->save($resource, 'og_description', $excerpt);
        }
    }

    private function translateResourceBodies(): void
    {
        foreach (Resource::query()->where('status', 'published')->get() as $resource) {
            $title = (string) $resource->translated('title', 'ro');
            $excerpt = (string) $resource->translated('excerpt', 'ro');

            if (in_array($resource->type, ['card', 'listening', 'reading', 'writing'], true)) {
                $this->save($resource, 'content', $excerpt);

                continue;
            }

            $content = (string) $resource->content;
            $content = str_replace('## '.$resource->title, '## '.$title, $content);
            $content = str_replace((string) $resource->excerpt, $excerpt, $content);

            $replacements = [
                "This resource is part of engleza.wiki's free English learning collection. It provides clear explanations, practical examples, and useful exercises to help you improve." => 'Această resursă face parte din colecția gratuită de învățare a limbii engleze de pe engleza.wiki. Include explicații clare, exemple practice și exerciții utile pentru a-ți îmbunătăți cunoștințele.',
                '### Key points' => '### Idei principale',
                '- Concept clearly explained' => '- Concept explicat clar',
                '- Multiple examples' => '- Mai multe exemple',
                '- Practice exercises' => '- Exerciții practice',
                '- Answer key' => '- Cheia răspunsurilor',
                '### Example' => '### Exemplu',
                '### When to use it' => '### Când se folosește',
                'Use this pattern for habits, routines, facts, and general truths.' => 'Folosește această structură pentru obiceiuri, rutine, fapte și adevăruri generale.',
            ];

            foreach ($replacements as $source => $translation) {
                $content = str_replace($source, $translation, $content);
            }

            $this->save($resource, 'content', $content);
        }
    }

    private function translateInteractiveContent(): void
    {
        $this->translateFields(CardResource::class, [
            'instructions' => [
                'Read the definition, then click to reveal the word.' => 'Citește definiția, apoi selectează cardul pentru a descoperi cuvântul.',
                'See the infinitive, then reveal the past and past participle.' => 'Privește forma de infinitiv, apoi descoperă formele de Past Simple și participiul trecut.',
                'Try to answer before you flip the card.' => 'Încearcă să răspunzi înainte de a întoarce cardul.',
            ],
        ]);

        $this->translateFields(ListeningResource::class, [
            'instructions' => [
                'Listen carefully and answer the questions below.' => 'Ascultă cu atenție și răspunde la întrebările de mai jos.',
            ],
        ]);

        $this->translateFields(ReadingResource::class, [
            'title' => [
                'A2 Reading: A Day in London' => 'Lectură A2: O zi în Londra',
                'B1 Reading: Why Learning Languages Matters' => 'Lectură B1: De ce este important să învățăm limbi străine',
                'B2 Reading: The Future of Remote Work' => 'Lectură B2: Viitorul muncii la distanță',
            ],
            'instructions' => [
                'Read the passage carefully, then answer the questions.' => 'Citește cu atenție textul, apoi răspunde la întrebări.',
            ],
        ]);

        $this->translateFields(WritingResource::class, [
            'instructions' => [
                'Use present simple tense. Include at least three activities for each part of the day.' => 'Folosește timpul Present Simple. Include cel puțin trei activități pentru fiecare parte a zilei.',
                'Include: a greeting, the reason for writing, the plan for the weekend, and a friendly ending.' => 'Include: o formulă de salut, motivul pentru care scrii, planul pentru weekend și o încheiere prietenoasă.',
                'Use past simple and past continuous. Try to include feelings and one memorable moment.' => 'Folosește timpurile Past Simple și Past Continuous. Încearcă să incluzi sentimente și un moment memorabil.',
                'Write an opinion essay. Present at least one advantage and one disadvantage before giving your opinion.' => 'Scrie un eseu de opinie. Prezintă cel puțin un avantaj și un dezavantaj înainte de a-ți exprima punctul de vedere.',
            ],
            'rubric' => [
                'Task achievement, organisation, vocabulary and grammar.' => 'Îndeplinirea cerinței, organizarea textului, vocabularul și gramatica.',
            ],
        ]);
    }

    private function save(Model $model, string $field, string $value): void
    {
        ContentTranslation::query()->updateOrCreate(
            [
                'translatable_type' => $model->getMorphClass(),
                'translatable_id' => $model->getKey(),
                'locale' => 'ro',
                'field' => $field,
            ],
            [
                'value' => $value,
                'status' => 'reviewed',
            ],
        );
    }
}
