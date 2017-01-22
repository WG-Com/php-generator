<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;

use Nette;
use Nette\InvalidStateException;
use Nette\Utils\Strings;


/**
 * Namespaced part of a PHP file.
 *
 * Generates:
 * - namespace statement
 * - variable amount of use statements
 * - one or more class declarations
 */
class PhpNamespace
{
	use Nette\SmartObject;

	/** @var string */
	private $name;

	/** @var bool */
	private $bracketedSyntax = FALSE;

	/** @var string[] */
	private $uses = [];

	/** @var ClassType[] */
	private $classes = [];


	public function __construct(string $name = NULL)
	{
		$this->name = (string) $name;
	}


	/** @deprecated */
	public function setName(string $name)
	{
		trigger_error(__METHOD__ . '() is deprecated, use constructor.', E_USER_DEPRECATED);
		$this->name = (string) $name;
		return $this;
	}


	/**
	 * @return string|NULL
	 */
	public function getName()
	{
		return $this->name ?: NULL;
	}


	/**
	 * @return static
	 * @internal
	 */
	public function setBracketedSyntax(bool $state = TRUE)
	{
		$this->bracketedSyntax = (bool) $state;
		return $this;
	}


	public function getBracketedSyntax(): bool
	{
		return $this->bracketedSyntax;
	}


	/**
	 * @throws InvalidStateException
	 * @return static
	 */
	public function addUse(string $name, string $alias = NULL, string &$aliasOut = NULL)
	{
		$name = ltrim($name, '\\');
		if ($alias === NULL && $this->name === Helpers::extractNamespace($name)) {
			$alias = Helpers::extractShortName($name);
		}
		if ($alias === NULL) {
			$path = explode('\\', $name);
			$counter = NULL;
			do {
				if (empty($path)) {
					$counter++;
				} else {
					$alias = array_pop($path) . $alias;
				}
			} while (isset($this->uses[$alias . $counter]) && $this->uses[$alias . $counter] !== $name);
			$alias .= $counter;

		} elseif (isset($this->uses[$alias]) && $this->uses[$alias] !== $name) {
			throw new InvalidStateException(
				"Alias '$alias' used already for '{$this->uses[$alias]}', cannot use for '{$name}'."
			);
		}

		$aliasOut = $alias;
		$this->uses[$alias] = $name;
		return $this;
	}


	/**
	 * @return string[]
	 */
	public function getUses(): array
	{
		return $this->uses;
	}


	public function unresolveName(string $name): string
	{
		if (in_array(strtolower($name), ['self', 'parent', 'array', 'callable', 'string', 'bool', 'float', 'int', ''], TRUE)) {
			return $name;
		}
		$name = ltrim($name, '\\');
		$res = NULL;
		$lower = strtolower($name);
		foreach ($this->uses as $alias => $for) {
			if (Strings::startsWith($lower . '\\', strtolower($for) . '\\')) {
				$short = $alias . substr($name, strlen($for));
				if (!isset($res) || strlen($res) > strlen($short)) {
					$res = $short;
				}
			}
		}

		if (!$res && Strings::startsWith($lower, strtolower($this->name) . '\\')) {
			return substr($name, strlen($this->name) + 1);
		} else {
			return $res ?: ($this->name ? '\\' : '') . $name;
		}
	}


	public function addClass(string $name): ClassType
	{
		if (!isset($this->classes[$name])) {
			$this->addUse($this->name . '\\' . $name);
			$this->classes[$name] = new ClassType($name, $this);
		}
		return $this->classes[$name];
	}


	public function addInterface(string $name): ClassType
	{
		return $this->addClass($name)->setType(ClassType::TYPE_INTERFACE);
	}


	public function addTrait(string $name): ClassType
	{
		return $this->addClass($name)->setType(ClassType::TYPE_TRAIT);
	}


	/**
	 * @return ClassType[]
	 */
	public function getClasses(): array
	{
		return $this->classes;
	}


	/**
	 * @return string PHP code
	 */
	public function __toString(): string
	{
		$uses = [];
		asort($this->uses);
		foreach ($this->uses as $alias => $name) {
			$useNamespace = Helpers::extractNamespace($name);

			if ($this->name !== $useNamespace) {
				if ($alias === $name || substr($name, -(strlen($alias) + 1)) === '\\' . $alias) {
					$uses[] = "use {$name};";
				} else {
					$uses[] = "use {$name} as {$alias};";
				}
			}
		}

		$body = ($uses ? implode("\n", $uses) . "\n\n" : '')
			. implode("\n", $this->classes);

		if ($this->bracketedSyntax) {
			return 'namespace' . ($this->name ? ' ' . $this->name : '') . " {\n\n"
				. Strings::indent($body)
				. "\n}\n";

		} else {
			return ($this->name ? "namespace {$this->name};\n\n" : '')
				. $body;
		}
	}

}
