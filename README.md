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
- Finally, `->html()` is a secure wrapper around `html_special_chars()`.

Basically, this is the standard PHP string API, with these benefits:

- Method syntax removes haystack/needle confusion.
- Everything is UTF-8 aware, using mbstring functions and an automatic charset header.

The `s()` function also implements JavaScript's string API:  
`->charAt()`, `->indexOf()`, `->lastIndexOf()`, `->match()`, `->replace()`, `->split()`, `->substr()`, `->substring()`, `->toLowerCase()`, `->toUpperCase()`, `->trim()`, `->trimLeft()`, `->trimRight()` and `->valueOf()`.

**`a()`** does the same thing for arrays.

Implemented methods are: `->count()`, `->has()` (instead of `in_array()`), `->search()`, `->shift()`, `->unshift()`, `->key_exists()`, `->implode()`, `->keys()`, `->values()`, `->pop()`, `->push()`, `->slice()`, `->splice()`, `->merge()`, `->map()`, `-reduce()` and `->sum()`.

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
      $looksLikeO = mb_convert_encoding("&#x213A;", "UTF-8", "HTML-ENTITIES");
      echo stringCompare("joeri", "j".$looksLikeO."eri"); // 0.8   

Note that the last line proves that the `s()` methods are UTF-8 aware, as 0.8 means a difference of one character.

Objects and types
-----------------

The `o()` function is used to convert an array or string to an object. A string is treated as JSON data.

      $o = o('{"key":"value"}');
      echo $o->key; // outputs "value"

A much more useful property however is the ability to cast objects or JSON data to a defined type:

      class IntKeyValue {
        /** @var int */
        public $key;
      };
      $o = o('{"key":"5"}')->cast("IntKeyValue");
      echo getclass($o) . " " . gettype($o->key); // O\IntKeyValue integer

The properties on the class that you are casting to can have type annotations that describe the type to convert to:

- `/** @var float */` : convert to a number 
- `/** @var string[] */` : convert to an array of string
- `/** @var MyType */`: convert to a nested complex type
- `/** @var array[int]MyType */` : convert to an array with int keys and MyType values

Supported primitive types are:

- **void**: becomes NULL
- **bool**/boolean
- **int**/integer: becomes NULL if conversion fails
- **float**/double: becomes NULL if conversion fails
- **string**
- **mixed**: leaves value unconverted
- **resource**: becomes NULL if it is not a resource
- **object**: uses o() to cast to stdObject (accepts JSON string)

Any piece of data that fails to convert to the right type becomes NULL. This is a quick and easy way to force JSON input to be in the right type.

**Tip:** you can convert any value to any type with `convertValue($value, $type)`. The `->cast()` method is a convenient wrapper around this functionality.

Positive input validation
-------------------------

As pointed out above, the `o()->cast()` method is a convenient way to force input to be of a specific type. However, to positively validate your input you want to verify not just the type, but also the range of values.

To this end, php-o implements [JSR-303 (Java Bean Validation)](http://docs.oracle.com/javaee/6/tutorial/doc/gircz.html).

TODO: rest of explanation
