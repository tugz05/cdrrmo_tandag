<?php

namespace App\Traits;

trait JTrait {
    public function fullname($user)
    {
        $fullname = $user->fname;
    
        if ($user->mname)
            $fullname .= ' ' . $user->mname;
    
        $fullname .= ' ' . $user->lname;
    
        if ($user->suffix)
            $fullname .= ' ' . $user->suffix;
    
        return trim($fullname);   
    }

    public function randomize($data = [])
    {
        $randomKey = array_rand($data);
        $randomValue = $data[$randomKey];

        return $randomValue;
    }

    public function getPercentage($total_items, $completed_items)
    {
        return $total_items > 0 ? ($completed_items / $total_items) * 100 : 0;
    }


    private function completed($default_responses) : int
    {
        $completed = 0;
        $total_items = 0;

        if (is_array($default_responses)) {
            foreach($default_responses as $response) {
                if (!is_null($response) && !empty($response)) {
                    $completed++;
                }

                $total_items++;
            }
        }

        $percentage = $this->getPercentage($total_items, $completed);

        return (int)$percentage;
    }

    public function generateVerificationCode(
        $prefix = '', 
        $characters = '0123456789', 
        $length = 6
    ) {
        $generatedCharacters = '';
    
        for ($i = 0; $i < $length; $i++) {
            $generatedCharacters .= $characters[rand(0, strlen($characters) - 1)];
        }

        $code = $prefix . $generatedCharacters;
    
        return $code;
    }
} 