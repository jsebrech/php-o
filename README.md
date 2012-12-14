php-o
=====

O-syntax for PHP

This is an experiment in meta-programming PHP to give it a saner API. This library requires PHP 5.3 and the mbstring module.

To start using it, include this at the top of your page:

    <?php namespace O; include "O.php";

Letter soup
-----------

**The heart of O are the three letter functions: `s()`, `a()`, and `o()`.**

Let's start with the **`s()`** function, which is used to add methods to a string:

    echo s("abc")->len();
      // 3
    echo s("abc")->substr(2);
      // c

**`s()`** turns all the standard string functions into methods with identical behavior:

- `s($haystack)->pos($needle)` instead of `str_pos($haystack, $needle)`, and also `->ipos()`, `->rpos()` and `->ripos()`
- `s($haystack)->explode($delimiter)` instead of `explode($delimiter, $haystack)`
- And similarly: `->trim()`, `->ltrim()`, `->rtrim()`, `->pad()`, `->len()`, `->tolower()`, `->toupper()`, `->substr()`, `->replace()`, `->ireplace()`, `->preg_match()`, `->preg_match_all()`, `->preg_replace()`, `->in_array()`.

Basically, this is the standard PHP string API, with these benefits:

- Method syntax removes haystack/needle confusion.
- Everything is UTF-8 aware, using mbstring functions and an automatic charset header.

The `s()` function also implements JavaScript's string API:  
`->charAt()`, `->indexOf()`, `->lastIndexOf()`, `->match()`, `->replace()`, `->split()`, `->substr()`, `->substring()`, `->toLowerCase()`, `->toUpperCase()`, `->trim()`, `->trimLeft()`, `->trimRight()` and `->valueOf()`.

**`a()`** does the same thing for arrays.

Implemented methods are: `->count()`, `->has()` (instead of `in_array()`), `->search()`, `->shift()`, `->unshift()`, `->key_exists()`, `->implode()`, `->keys()`, `->values()`, `->pop()`, `->push()`, `->slice()`, `->splice()` and `->merge()`.

**Example of the `s()` function, string similarity algorithm:**

      // adapted from 
      // http://cambiatablog.wordpress.com/2011/03/25/algorithm-for-string-similarity-better-than-levenshtein-and-similar_text/
      function stringCompare($a, $b) {
        $lengthA = s($a)->len();
        $lengthB = s($b)->len();
        $i = 0;
        $segmentCount = 0;
        $segments = array();
        $segment = '';
        while ($i < $lengthA) {
          $char = s($a)->substr($i, 1);
          if (s($b)->pos($char) !== FALSE) {
            $segment = $segment.$char;
            if (s($b)->pos($segment) !== FALSE) {
              $segmentPosA = $i - s($segment)->len() + 1;
              $segmentPosB = s($b)->pos($segment);
              $positionDiff = abs($segmentPosA - $segmentPosB);
              $positionFactor = ($lengthA - $positionDiff) / $lengthB;
              $lengthFactor = s($segment)->len()/$lengthA;
              $segments[$segmentCount] = array( 
                'segment' => $segment, 
                'score' => ($positionFactor * $lengthFactor)
              );
            } else {
              $segment = '';
              $i--;
              $segmentCount++;
            };
          } else {
            $segment = '';
            $segmentCount++;
          };
          $i++;
        };
        $getScoreFn = function($v) { return $v['score']; };
        $totalScore = a(a($segments)->map($getScoreFn))->sum();
        return $totalScore;
      }
      echo stringCompare("joeri", "jori"); // 0.9    

**TODO: rest of explanation**