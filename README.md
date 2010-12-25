# Johnny_Router

This little project is my solution to the problem of routing URLs to application actions and, conversely, generating URLs in PHP applications. In short, it translates strings to arrays and back.

My goals when writing this library were to build something simple, lightweight and to avoid any implicit "magic".

## How to use

Three most important methods exposed by the router are:

* `connect($path, $params, $options)` - define new rule
* `match($path)` - translate URL (string) to an array
* `createUrl($params)` - create URL for specified array

In typical MVC application, you'd use `match` somewhere in your controller (probably only once in the whole application), `createUrl` in views and controllers, and `connect` in your routing definitions.

There are also other public methods, useful for dealing with aliases, which will be covered later.

### Basic usage

	$r = new Johnny_Router();
	$r->connect('/abc/:id/', array('action' => 'AbcAction'))

Then:

	$r->match('/abc/12/') => array('action' => 'AbcAction', 'id' => '12'))
	$r->match('/abc/foo/') => array('action' => 'AbcAction', 'id' => 'foo'))
	$r->match('/abc/foo/foo2/') => array('action' => 'AbcAction', 'id' => 'foo/foo2/'))
	$r->match('/abc/') => false

	$r->createUrl(array('action' => 'AbcAction', 'id' => 'foo')) => '/abc/foo/'
	$r->createUrl(array('action' => 'AbcAction', 'id' => 12)) => '/abc/12/'

### Parameter expressions

Any occurrence of the form `:name` (e.g. `:id`) in the pattern is replaced by regular expression. By default it is `.*?` which will catch any string.

You can replace it with something else, e.g:

	$r->connect('/abc/:id/', array('action' => 'AbcAction', 'id' => '\d+'))
	$r->match('/abc/12/') => array('action' => 'AbcAction', 'id' => '12'))
	$r->match('/abc/foo/') => false

	$r->connect('/abc/:id/', array('action' => 'AbcAction', 'id' => '[^/]+')) // [^/] is "anything but a slash"
	$r->match('/abc/12/') => array('action' => 'AbcAction', 'id' => '12'))
	$r->match('/abc/foo/') => array('action' => 'AbcAction', 'id' => 'foo'))
	$r->match('/abc/foo/foo2/') => false

The regular expression will by wrapped in parentheses. Please do not use parentheses in your expression, as it will mess things when matching the pattern.

### Constants

As you can see, when the router matches URL with one of the patterns, it may pass some constants, like `action` in the previous examples. (`action` is not any special type of constant, you can use any other name, or, for example, pass `controller` as well).

To sum up, there are two types of elements in the `$params` array that you pass to the `connect()` method:

* if the key in `$params` is the same as one of the `:name` parameters in the pattern, the value is regular expression that limits possible parameter values (just like `'id' => '\d+'` in previous examples)
* otherwise it's a constant (like `'action' => 'AbcAction'`)

### Rules without a path

It's also possible to define a rule like this:

	$r->connect(array('id'), array('action' => 'NewsShow', 'id' => '\d+'))

Such rule will only be used when creating a URL and won't matter when calling `match`. It makes sense only when using `onCreate`.

### onCreate

`onCreate` is an option that lets you define a function, that will be called when creating the URL. For example:

	function filterPost($r, $args) {
		$args['id'] = $args['post']['id'];
		unset($args['post']);
		return $r->createUrl($args);
	}
	$r->connect('/post/:id/', array('action' => 'PostShow', 'id' => '\d+'))
	$r->connect(array('post'), array('action' => 'PostShow'), array('onCreate' => 'filterPost')

Then:

	$r->match('/post/12/') => array('action' => 'PostShow', 'id' => 12)
	$r->createUrl(array('action' => 'PostShow', 'id' => 12)) => '/post/12/'

And:

	$post = array('id' => '69', 'title' => 'my-post')
	$r->createUrl(array('action' => 'PostShow', 'post' => $post)) => '/post/69/'

Why is it useful?

If you define you routes like this, when calling `createUrl`, you don't need to know which attributes of the post will be needed. It's easy to change the URLs scheme to something like `/post/69/my-post/`:

	function filterPost($r, $args) {
		$args['id'] = $args['post']['id'];
		$args['title'] = $args['post']['title'];
		unset($args['post']);
		return $r->createUrl($args);
	}
	$r->connect('/post/:id/:title/', array('action' => 'PostShow', 'id' => '\d+'))
	$r->connect(array('post'), array('action' => 'PostShow'), array('onCreate' => 'filterPost')

In such situation, the following call:

	$r->createUrl(array('action' => 'PostShow', 'post' => $post))

will be handled this way:

* router will examine all the rules and find out, that it may use the rule, in which there is a constant `'action' => 'PostShow'` and the `post` variable
* for this rule there is an `onCreate` callback function, so the router will run it, passing itself and the arguments array
* the callback function extracts post's id and title, and runs the router again
* this time the router finds a rule without `onCreate` and returns `/post/69/my-post/`

At any time, you can change the URLs scheme so it includes post's creation date or anything else, without any changes to your views and controllers.

### Aliases

In order to make `createUrl` calls shorter, Johnny_Router provides aliases.

	$r->connect('/abc/:id/', array('action' => 'AbcAction'));
	$r->alias('AbcAlias', array('action' => 'AbcAction'), array('id'));

From now on you can use `fromAlias` method, e.g.:

	$r->fromAlias('AbcAlias', array(12));

The router will internally run:

	$r->createUrl(array('action' => 'AbcAction', 'id' => 12))

Well, so far it doesn't look much shorter. But the router provides also one more method, called `url`, which let's you use both styles of creating URLs (with arrays and with aliases). The following examples are equivalent:

	$r->url('AbcAlias', 12)
	$r->url(array('action' => 'AbcAction', 'id' => 12))

Internally the `url` method looks at its first argument. If it's array, it calls `createUrl`. If it's string, it calls `fromAlias`.

### Defininig aliases

It may be tedious to type:

	$r->connect('/abc/:id/', array('action' => 'AbcAction'));
	$r->alias('AbcAlias', array('action' => 'AbcAction'), array('id'));

so you can pass the `alias` option:

	$r->connect('/abc/:id/', array('action' => 'AbcAction'), array('alias' => 'AbcAlias');

The above statements are equivalent.

The `alias` method makes sense if you want to set some variable to constant value, e.g.:

	$r->connect('/abc/:id/', array('action' => 'AbcAction'));
	$r->alias('Abc12Alias', array('action' => 'AbcAction', 'id' => 12), array());
	$r->url('Abc12Alias') => '/abc/12/'

But it's rarely needed.

### Aliases and onCreate together

Below is a real example of how I often define routes in my applications:

	$r->connect('/news/:year-:month/:id-:slug/',
		array('action' => 'PubNewsShow', 'year' => '\d{4}', 'month' => '\d{2}', 'id' => '\d+'))
	$r->connect('item', array('action' => 'PubNewsShow'),
		array('alias' => 'PubNewsShow', 'onCreate' => 'filterNews')
	function filterNews($r, $args) {
		// here extract year, month, slug and id, and then remove item from the arguments
	}

Usage example:

	$r->url(array('action' => 'PubNewsShow', 'item' => $news))
	$r->url('PubNewsShow', $news)

## Notes

Johnny_Router was not optimized at all. If you need to create many URLs on one page (a few thousands), it may turn out to be too slow. However, I think optimizing the router is not really hard (you could index the routes by one of the constants, so you wouldn't need to call `matchArgs` for all the rules).

One of my goals when creating this library was to make url creation syntax as short as possible, so my views are plain and simple. I think I succeeded, but at the cost of complex route definitions. Any ideas on how to simplify them are welcome.

Sometimes it's useful to inherit the router or create a proxy object that will implement your own, common "patterns" in route definitions.
