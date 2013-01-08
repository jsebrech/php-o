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

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>TodoPHP</title>
	<link rel="stylesheet" href="css/base.css">
</head>
<body>
  <form method="POST">
  <input type="hidden" name="csrftoken" value="<?php echo $session->getCSRFToken(); ?>" />
	<section id="todoapp">
		<header id="header">
			<h1>todos</h1>
			<input type="submit" class="add" name="action-add" value="+" />
			<input id="new-todo" name="new-todo" type="text" placeholder="What needs to be done?" autofocus>
			<div class="error"><?php echo s($errorMsg)->html(); ?></div>
		</header>
		<!-- This section should be hidden by default and shown when there are todos -->
		<section id="main">
			<ul id="todo-list">
			<?php foreach($session->items as $item) { ?>
				<li class="<?php echo $item->completed ? "completed" : ""; ?>">
					<div class="view">
					  <input type="hidden" 
					         name="item-<?php echo $item->id; ?>" 
					         value="1" />
						<input class="toggle" 
						       name="completed-<?php echo $item->id; ?>" 
						       type="checkbox" 
						       <?php echo $item->completed ? "checked" : ""; ?> />
						<input class="edit" 
						       name="message-<?php echo $item->id; ?>" 
						       type="text" 
						       maxlength="200" 
						       value="<?php echo s($item->message)->html(); ?>">
					</div>
				</li>
			<?php }; ?>
			</ul>
		</section>
		<!-- This footer should hidden by default and shown when there are todos -->
		<footer id="footer">
		  <input type="submit" 
		         name="action-save" 
		         class="footer-btn"
		         style="<?php echo count($session->items) ? "" : "display:none;"; ?>"
		         value="Save changes" />
			<!-- This should be `0 items left` by default -->
			<span id="todo-count"><?php 
			  if ($todoCount == 1) {
			    echo "<strong>1</strong> item left";
			  } else {
			    echo "<strong>".$todoCount."</strong> items left";
			  };
			  ?></span>
			<input type="submit" name="action-clear-completed" 
			       class="footer-btn" style="<?php echo $completedCount ? "" : "display:none"; ?>"
			       value="Clear completed (<?php echo $completedCount; ?>)" />
		</footer>
	</section>
	</form>
	<footer id="info">
		<p style="font-size: 130%;">Demo for <a href="https://github.com/jsebrech/php-o">php-o</a></p>
		<p>Based on <a href="http://todomvc.com">TodoMVC</a></p>
	</footer>
</body>
</html>
