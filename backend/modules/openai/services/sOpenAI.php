<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\openai\services;

class sOpenAI
{

    /**
     * @var
     */
    protected static $instance;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    public function getChatGPTAnswer($token, $Question, $model = 'gpt-3.5-turbo'){
        $client = \OpenAI::client($token);

        if($model=='gpt-3.5-turbo'){
            try {
                $response = $client->chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'user', 'content' => $Question],
                    ],
                ]);
            } catch (\Exception $e) {
                return ['error' => 1, 'data' => 'Exception: '.  $e->getMessage()];
            }
            $response->toArray();
            if(!empty($response['choices'][0]['message']['content'])){
                return ['error' => 0, 'data' => 'Success', 'answer'=>$response['choices'][0]['message']['content'], 'response'=>$response];
            }
        }

        // TODO GPT-4 API waitlist https://openai.com/waitlist/gpt-4-api , SOON
        if($model=='gpt-4'){
            /*$stream = $client->chat()->createStreamed([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'user', 'content' => $message_text],
                ],
            ]);

            $rowsArr=[];
            foreach($stream as $response){
                $rowsArr = $response->choices[0]->toArray();
            }
            echo '<pre>';
            print_r($rowsArr);
            echo '</pre>';*/
        }

        return ['error' => 1, 'data' => 'Unknown error'];
    }

    public function getImg($token, $prompt, $size='256x256'){
        $client = \OpenAI::client($token);
        try {
            $response = $client->images()->create([
                'prompt' => $prompt,
                'n' => 1,
                'size' => $size,
                'response_format' => 'url',
            ]);
            $response->created; // 1589478378
            $response->toArray(); // ['created' => 1589478378, data => ['url' => 'https://oaidalleapiprodscus...', ...]]
            if(!empty($response['data'][0]['url'])){
                return ['error' => 0, 'data' => 'Success', 'url'=>$response['data'][0]['url']];
            }
        } catch (\Exception $e) {
            return ['error' => 1, 'data' => 'Exception: '.  $e->getMessage()];
        }

        return ['error' => 1, 'data' => 'Unknown error'];
    }


    public function getAudioTranscribe($token, $audioFile){
        if(!file_exists($audioFile)){
            return ['error' => 1, 'data' => 'audioFile not found'];
        }
        $client = \OpenAI::client($token);
        $response = $client->audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($audioFile, 'r'),
            'response_format' => 'verbose_json',
        ]);

        $response->toArray(); // ['task' => 'transcribe', ...]

        if(!empty($response['text'])){
            return ['error' => 0, 'data' => 'Success', 'text'=>$response['text']];
        }

        return ['error' => 1, 'data' => 'Unknown error'];
    }

}