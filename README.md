# php-o

*O-syntax for PHP*

This is an experiment in meta-programming PHP to give it a saner API. This library requires PHP 5.3 and the mbstring module.

To start using it, include this at the top of your page:

    <?php namespace O; include "O.php";

You can use composer to install "jsebrech/o", and then load it like this:

    <?php namespace O; include "vendor/autoload.php"; O::init();

It is also possible to load each of the pieces described below separately:

    include("path/to/O/StringClass.php");
    echo O\s("foo")->replace("foo", "bar");

## Table of Contents

* [php-o](#php-o)
    * [Table of Contents](#table-of-contents)
    * [Strings and Arrays](#strings-and-arrays)
    * [Objects and Types](#objects-and-types)
    * [Input Validation](#input-validation)
    * [Chainables](#chainables)
    * [Session Handling](#session-handling)
    * [PDO](#pdo)
    * [Example Application](#example-application)

## Strings and Arrays

**The heart of O are the three letter functions: `s()`, `a()`, and `o()`.**

The **`s()`** function is used to add methods to a string:

    echo s("abc")->len();
      // 3
    echo s("abc")->substr(2);
      // c

**`s()`** turns all the standard string functions into methods with identical behavior:

- `s($haystack)->pos($needle)` instead of `str_pos($haystack, $needle)`, and also `->ipos()`, `->rpos()` and `->ripos()`
- `s($haystack)->explode($delimiter)` instead of `explode($delimiter, $haystack)`
- And similarly: `->trim()`, `->ltrim()`, `->rtrim()`, `->pad()`, `->len()`, `->tolower()`, `->toupper()`, `->substr()`, `->replace()`, `->ireplace()`, `->preg_match()`, `->preg_match_all()`, `->preg_replace()`, `->in_array()`.
- Finally, `->html()` is a secure wrapper around `html_special_chars()`.

The array indexing operator is supported:

- In PHP 5.3: `$s = s("abc"); echo $s[2]; // "c"`
- In PHP 5.4: `echo s("abc")[2]; // "c"`

Basically, this is the standard PHP string API, with these benefits:

- Method syntax removes haystack/needle confusion.
- Everything is UTF-8 aware, using mbstring functions and an automatic charset header.

The `s()` function also implements JavaScript's string API:  
`->charAt()`, `->indexOf()`, `->lastIndexOf()`, `->match()`, `->replace()`, `->split()`, `->substr()`, `->substring()`, `->toLowerCase()`, `->toUpperCase()`, `->trim()`, `->trimLeft()`, `->trimRight()` and `->valueOf()`.

**`a()`** does the same thing for arrays.

Implemented methods are: `->count()`, `->has()` (instead of `in_array()`), `->search()`, `->shift()`, `->unshift()`, `->key_exists()`, `->implode()`, `->keys()`, `->values()`, `->pop()`, `->push()`, `->slice()`, `->splice()`, `->merge()`, `->map()`, `-reduce()`, `->sum()`, `->begin()` (instead of `reset()`), `->next()`, `->current()`, `->each()` and `->end()`.

**Example of the `s()` function, string similarity algorithm:**

      // adapted from
      // http://cambiatablog.wordpress.com/2011/03/25/algorithm-for-string-similarity-better-than-levenshtein-and-similar_text/
      function stringCompare($a, $b) {
        $a = s($a); $b = s($b);
        $i = 0;
        $segmentCount = 0;
        $segments = array();
        $segment = '';
        while ($i < $a->len()) {
          if ($b->pos($a[$i]) !== FALSE) {
            $segment = $segment.$a[$i];
            if ($b->pos($segment) !== FALSE) {
              $segmentPosA = $i - s($segment)->len() + 1;
              $segmentPosB = $b->pos($segment);
              $positionDiff = abs($segmentPosA - $segmentPosB);
              $positionFactor = ($a->len() - $positionDiff) / $b->len();
              $lengthFactor = s($segment)->len()/$a->len();
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

## Objects and Types

The `o()` function is used to convert an array or string to an object. A string is treated as JSON data.

      $o = o('{"key":"value"}');
      echo $o->key; // outputs "value"

It can be used to cast objects or JSON data to a defined type:

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
- **DateTime**: converts a string or int to a DateTime instance

Any piece of data that fails to convert to the right type becomes NULL. This is an easy way to force JSON input to be in the right type.

**Tips:**

- You can convert any value to any type with `convertValue($value, $type)`. The `->cast()` method is a convenient wrapper around this functionality.
- You can "fix" the types of the properties on any object by casting it to its own type. (e.g. ensure that integers are not secretly strings)

Another useful thing you can do is filter the $_REQUEST array by casting it to a defined type:

      class RequestParams {
        /** @var string */
        public $foo = "";
        /** @var int */
        public $bar = 1;
      }  
      $request = o($_REQUEST)->cast("RequestParams");
      print_r($request);

When you call that script with ?foo=test it will output:

      O\RequestParams Object
      (
        [foo] => test
        [bar] => 1
      )

The `foo` parameter is taken from the $_REQUEST array, but the `bar` gets its default value instead from the type definition. If bar was specified, it would automatically get converted to an int if possible (and become NULL otherwise).

## Input Validation

As pointed out in the previous section, the `o()->cast()` method is a convenient way to force input to be of a specific type. However, to positively validate your input you want to verify not just the type, but also the range of values.

To this end, php-o implements [JSR-303 (Java Bean Validation)](http://docs.oracle.com/javaee/6/tutorial/doc/gircz.html).

It's easiest to explain using an example:

      class Validatable {
        /**
         * @var string
         * @NotNull
         * @Size(min=0,max=5)
         */
        public $text = "";
    
        /**
         * @var float
         * @Min(0)
         */
        public $positive = 0;
      }
      $obj = o(array("text" => "123456", "positive" => -1))->cast("Validatable");
      $validation = Validator::validate($obj);
      print_r($validation);

This will output these errors:

      Array
      (
          [0] => O\ConstraintViolation Object
              (
                  [message] => Size must be between 0 and 5
                  [constraint] => Size
                  [rootObject] => O\Validatable Object
                      (
                          [text] => 123456
                          [positive] => -1
                      )
                  [propertyPath] => text
                  [invalidValue] => 123456
              )
          [1] => O\ConstraintViolation Object
              (
                  [message] => Must be >= 0
                  [constraint] => Min
                  [rootObject] => O\Validatable Object
                      (
                          [text] => 123456
                          [positive] => -1
                      )
                  [propertyPath] => positive
                  [invalidValue] => -1
              )
      )

In short, the Validator::validate method will perform the checks as specified by the annotation comments for each of the properties. The result is an array of validation errors, this array being empty if all properties are valid.

The supported annotations are these:

- **@Null**: can be used to override an @NotNull in a subclass.
- **@NotNull**: property does not accept NULL as a value. If you do not specify this, NULL is a valid value (even if it fails other validation rules).
- **@NotEmpty**: Same as @NotNull, and also string cannot be "", or whitespace and array must have at least one item.
- **@Valid**: recursively validate this property (for object properties with a type annotation)
- **@AssertTrue**
- **@AssertFalse**
- **@Min(value)**: property must be >= the value
- **@Max(value)**: property must be <= the value
- **@Size(min=value,max=value)**: array or string length must fit these constraints (supports specifying just min or max)
- **@DecimalMin(value)**: same as @Min, but can deal with large numbers
- **@DecimalMax(value)**: same as @Max, but can deal with large numbers
- **@Digits(integer=value,fraction=value)**: the specified number must have at most this many integer or fractional digits
- **@Past**: date must be in the past (supports DateTime instances, date strings and integer timestamps)
- **@Future**: date must be in the future

The Validator is a pluggable framework. You can easily add your own annotations. Look at the O source code to see how.

## Chainables

The `c()` function implements a fluent API on objects that are not fluent by default. In other words, it wraps an object so that the methods on that object return a chainable object.

That means you can do something like this:

      echo c(s("ababa"))->explode("b")->implode("c");
      // outputs acaca

In short, you can do jQuery-style chaining of methods.

If at any point, you want to get the object that is inside the chainable, you can use the `->raw()` method:

      $s = c(s("123abcxxx"))->substr(3)->rtrim("x")->raw();
      // $s === "abc"

You can use the `c()` function on any type, not just the special types provided by O (e.g. on the DateTime type). The return values are converted to smart types if they are primitives like string or array:

      echo c(new \DateTime())->format("Y-m-d")->explode("-")->pop();
      // contrived example to output the current day

Shorthand functions are provided:

- cs() == c(s())
- ca() == c(a())
- co() == c(o())

## Session Handling

O sets up sessions so they are secure by default.

When you do `session_start()` O guarantees the following:

- The session cookie has the httpOnly flag, and the secure flag if the session was created over HTTPS
- The session id will not be passed in the URL, but only via cookie
- The session name is changed from the default
- The session id is changed on the first request to prevent session fixation

Some convenience functionality is provided to protect against CSRF attacks. To use this:

1. Put this code in your form:  

      &lt;input type="hidden" name="csrftoken" value="<?php echo get_csrf_token(); ?>" />

2. Put everything that processes the form inside this if:
   
      if (is_csrf_protected()) { ...

While it's not perfect, it should suffice as a basic level of precaution.

There's also a Session wrapper class to give it an OO taste:

      $session = new Session();
      echo $session->foo; // == $_SESSION["foo"], isset implicitly performed
      echo $session->getCSRFToken(); // == get_csrf_token();
      if (!$session->isCSRFProtected()) die(); // == is_csrf_protected();

## PDO

PDO is wrapped to improve its API.

The fetch methods from the PDO statement are added directly to the PDO object:

    $db = new O\PDO("sqlite::memory:");
    
    $rows = $db->fetchAll(
        "select * from test where id <> :id",
        array("id" => 2) // bound parameter by name
    );
    
    $row = $db->fetchRow(
        "select * from test where id = ?",
        array(3) // bound parameter by position
    );

    $col = $db->fetchColumn(
        "select description, id from test where id <> :id",
        array("id" => 1), // NULL to skip
        1 // return second column
    );

It also adds one new fetch method to fetch a single value:

    $value = $db->fetchOne(
        "select description from test where id = :id",
        array("id" => 2)
    );

Parameter binding supports binding more than one parameter.

Anonymous binding:

    $value = $db->prepare(
        "select count(*) from test where id <> ? and id <> ?"
    )->bindParams(array(2, 3))->execute()->fetchColumn(0);

Named binding (via object or associative array):

    $params = new StdClass();
    $params->id = 4;
    $params->desc = "foo";
    $stmt = $db->prepare(
        "select description from test where id = :id and description <> :desc");
    $value = $stmt->bindParams($params)->execute()->fetchColumn(0);

Notice that the API is fluent (allows chained calls).

You may disable this through the *fluent* option:

    $db = new O\PDO("sqlite::memory:", "", "", array("fluent" => false));

There are also shorthand methods for basic CRUD operations:

    $insertId = $db->insert(
        "test", // table
        array("description" => "foo")
    );
    // uses PDO::lastInsertId to return the id
    
    $count = $db->update(
        "test",
        array("description" => "foo"), // set to this
        "id >= :id1 and id <= :id2", // where
        array("id1" => 2, "id2" => 6) // where parameters
    );

    $count = $db->delete(
        "test",
        "id >= :id1 and id <= :id2",
        array("id1" => 2, "id2" => 6)
    );

You can attach a profiler to get per-query profiles:

    $profiler = new O\PDOProfiler();
    $db->setProfiler($profiler);
    
    $db->query("select count(*) from test where id = :id", array("id" => 6));
    $profiles = $profiler->getProfiles();
    
    print_r($profiles);
    -->
    Array(
        [0] => Array(
                [0] => 7.7009201049805E-5 = elapsed time
                [1] => 1419201522.886 = start time
                [2] => select count(*) from test where id = :id
                [3] => Array([:id] => 6)
        )
    )

## Example Application

There is a [demo app](https://github.com/jsebrech/o-demo) that shows how O can be used in practice. This also shows off the ability to use HTML templating via the `o()->render()` method. There's also a [demo app](https://github.com/jsebrech/o-demo-rest) showing how to build a web service.
