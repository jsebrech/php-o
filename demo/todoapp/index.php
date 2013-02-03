<?php namespace O; include "../../O.php";

$session = new Session();

if (!is_array($session->items)) {
// if no tasks yet, create one
  $session->items = array(o(array(
    "id" => TodoItem::nextId(),
    "message" => "Create a demo app",
    "completed" => TRUE
  ))->cast("TodoItem"));
};

$errorMsg = "";

if ($session->isCSRFProtected()) {
  // list of ids of submitted items
  $ids = ca($_REQUEST)->keys()->filter(
    function($key) { return s($key)->pos("item-") === 0; })->map(
    function($key) { return s($key)->substr(5); })->raw();

  // read the submitted items and delete / update
  foreach ($ids as $id) {
    $item = o(array(
      "id" => $id,
      "message" => isset($_REQUEST["message-".$id]) ? $_REQUEST["message-".$id] : "",
      "completed" => isset($_REQUEST["completed-".$id]) ? $_REQUEST["completed-".$id] : FALSE
    ))->cast("TodoItem");
    // if item should be deleted
    if (s($item->message)->trim() == "") {
      $session->items = a($session->items)->filter(
        function($o) use($id) { return $o->id != $id; }
      );
    // if item should be updated
    } else {
      $errors = Validator::validate($item);
      // save to session if valid
      if (count($errors) == 0) {
        foreach ($session->items as $i => $stored) {
          if ($stored->id === $item->id) {
            $session->items[$i] = $item;
          };
        };
      } else {
        $errorMsg = $errors[0]->message;
      };
    };
  };
  // add an item if needed
  if (isset($_REQUEST["action-add"])) {
    $item = o(array(
      "id" => TodoItem::nextId(),
      "message" => isset($_REQUEST["new-todo"]) ? $_REQUEST["new-todo"] : "",
      "completed" => FALSE
    ))->cast("TodoItem");
    $errors = Validator::validate($item);
    // save to session if valid
    if (count($errors) == 0) {
      $session->items[] = $item;
    } else {
      $errorMsg = $errors[0]->message;
    };  
  // delete all the completed items
  } else if (isset($_REQUEST["action-clear-completed"])) {
    $session->items = a($session->items)->filter(
      function($o) { return !$o->completed; }
    );
  };
};

$completedCount = ca($session->items)->filter(
  function($o) { return $o->completed; }
  )->count();
$todoCount = count($session->items) - $completedCount;

o(compact(
  "session", "errorMsg", "completedCount", "todoCount"
))->render("html/main.phtml");

exit;

class TodoItem {
  /** 
   * @var int 
   */
  public $id = -1;
  /** 
   * @var string 
   * @Size(max=200)
   * @NotEmpty
   */
  public $message = "";
  /** 
   * @var bool 
   */
  public $completed = FALSE;
  
  public static function nextId() {
    $session = new Session();
    if (!isset($session->nextItemID)) {
      $session->nextItemID = 0;
    };
    return $session->nextItemID++;
  }
}
