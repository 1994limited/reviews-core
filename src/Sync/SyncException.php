<?php

namespace NineteenNinetyFour\Reviews\Core\Sync;

use RuntimeException;

/**
 * A sync couldn't finish: the platform refused the keys, or couldn't be
 * reached. The message is fit to show an editor.
 */
class SyncException extends RuntimeException {}
