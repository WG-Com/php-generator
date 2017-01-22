<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;

use Nette;
use Nette\Utils\Strings;


/**
 * Instance of PHP file.
 *
 * Generates:
 * - opening tag (<?php)
 * - doc comments
 * - one or more namespaces
 */
class PhpFile
{
	use Nette\SmartObject;

	/** @var string|NULL */
	private $comment;

	/** @var PhpNamespace[] */
	private $namespaces = [];


	/**
	 * @param  string|NULL
	 * @return static
	 */
	public function setComment($val)
	{
		$this->comment = $val ? (string) $val : NULL;
		return $this;
	}


	/**
	 * @return string|NULL
	 */
	public function getComment()
	{
		return $this->comment;
	}


	/**
	 * @return static
	 */
	public function addComment(string $val)
	{
		$this->comment .= $this->comment ? "\n$val" : $val;
		return $this;
	}


	public function addClass(string $name): ClassType
	{
		return $this
			->addNamespace(Helpers::extractNamespace($name))
			->addClass(Helpers::extractShortName($name));
	}


	public function addInterface(string $name): ClassType
	{
		return $this
			->addNamespace(Helpers::extractNamespace($name))
			->addInterface(Helpers::extractShortName($name));
	}


	public function addTrait(string $name): ClassType
	{
		return $this
			->addNamespace(Helpers::extractNamespace($name))
			->addTrait(Helpers::extractShortName($name));
	}


	/**
	 * @param  string NULL means global namespace
	 */
	public function addNamespace(string $name): PhpNamespace
	{
		if (!isset($this->namespaces[$name])) {
			$this->namespaces[$name] = new PhpNamespace($name);
		}
		return $this->namespaces[$name];
	}


	/**
	 * @return string PHP code
	 */
	public function __toString(): string
	{
		foreach ($this->namespaces as $namespace) {
			$namespace->setBracketedSyntax(count($this->namespaces) > 1 && isset($this->namespaces[NULL]));
		}

		return Strings::normalize(
			"<?php\n"
			. ($this->comment ? "\n" . Helpers::formatDocComment($this->comment . "\n") . "\n" : '')
			. implode("\n\n", $this->namespaces)
		) . "\n";
	}

}
