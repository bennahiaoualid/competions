<?php

namespace Database\Seeders;

use App\Models\GuestUsers\Choice;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GlobalResponsesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // جلب قائمة المستخدمين العشوائية
        $users = User::inRandomOrder()->limit(50)->get(); // عدد المستخدمين العشوائيين

        foreach ($users as $user) {
            // عدد الإجابات العشوائية لكل مستخدم (بين 5 إلى 10 إجابات)
            $answersCount = rand(2, 3);

            // قائمة الأسئلة التي أجاب عليها المستخدم
            $answeredQuestions = [];

            for ($i = 0; $i < $answersCount; $i++) {
                // اختيار سؤال عشوائي لم يتم الإجابة عليه سابقًا
                $question = GlobalQuestion::whereNotIn('id', $answeredQuestions)
                    ->inRandomOrder()
                    ->first();

                if (!$question) break; // إذا لم يتبقَ أسئلة، نخرج من الحلقة

                // جلب جميع الخيارات المرتبطة بالسؤال
                $choices = Choice::where('question_id', $question->id)->get();

                if ($choices->isEmpty()) continue; // تجنب الأسئلة بدون خيارات

                // اختيار خيار عشوائي
                $choice = $choices->random();

                // التحقق مما إذا كان الخيار صحيحًا
                $isCorrect = $choice->correct;

                // تحديد النتيجة إذا كان الجواب صحيحًا
                $score = $isCorrect ? rand(10, $question->score) : 0;

                // مدة الإجابة بين 60 و 100 ثانية
                $responseDuration = rand(60, 100);

                // إدخال البيانات في `global_responses`
                GlobalResponse::create([
                    'question_id' => $question->id,
                    'choice_id' => $choice->id,
                    'user_id' => $user->id,
                    'score' => $score,
                    'response_duration' => $responseDuration,
                ]);

                // إضافة السؤال إلى قائمة الأسئلة المجابة لمنع التكرار
                $answeredQuestions[] = $question->id;
            }
        }
    }
}
