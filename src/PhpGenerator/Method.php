<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;

use Nette;


/**
 * Method or function description.
 *
 * @property string|NULL $body
 */
final class Method extends Member
{
	/** @var array of name => Parameter */
	private $parameters = [];

	/** @var array of name => bool */
	private $uses = [];

	/** @var string|NULL */
	private $body = '';

	/** @var bool */
	private $static = FALSE;

	/** @var bool */
	private $final = FALSE;

	/** @var bool */
	private $abstract = FALSE;

	/** @var bool */
	private $returnReference = FALSE;

	/** @var bool */
	private $variadic = FALSE;

	/** @var PhpNamespace|NULL */
	private $namespace;

	/** @var string|NULL */
	private $returnType;

	/** @var bool */
	private $returnNullable;


	/**
	 * @param  \ReflectionFunctionAbstract|callable
	 * @return static
	 */
	public static function from($from)
	{
		return (new Factory)->fromFunctionReflection(
			$from instanceof \ReflectionFunctionAbstract ? $from : Nette\Utils\Callback::toReflection($from)
		);
	}


	public function __construct(string $name = NULL)
	{
		parent::__construct((string) $name);
	}


	/**
	 * @return string  PHP code
	 */
	public function __toString(): string
	{
		$parameters = [];
		foreach ($this->parameters as $param) {
			$variadic = $this->variadic && $param === end($this->parameters);
			$hint = $param->getTypeHint();
			$parameters[] = ($hint ? ($param->isNullable() ? '?' : '') . ($this->namespace ? $this->namespace->unresolveName($hint) : $hint) . ' ' : '')
				. ($param->isReference() ? '&' : '')
				. ($variadic ? '...' : '')
				. '$' . $param->getName()
				. ($param->hasDefaultValue() && !$variadic ? ' = ' . Helpers::dump($param->getDefaultValue()) : '');
		}
		$uses = [];
		foreach ($this->uses as $param) {
			$uses[] = ($param->isReference() ? '&' : '') . '$' . $param->getName();
		}

		return Helpers::formatDocComment($this->getComment() . "\n")
			. ($this->abstract ? 'abstract ' : '')
			. ($this->final ? 'final ' : '')
			. ($this->getVisibility() ? $this->getVisibility() . ' ' : '')
			. ($this->static ? 'static ' : '')
			. 'function '
			. ($this->returnReference ? '&' : '')
			. $this->getName()
			. '(' . implode(', ', $parameters) . ')'
			. ($this->uses ? ' use (' . implode(', ', $uses) . ')' : '')
			. ($this->returnType ? ': ' . ($this->returnNullable ? '?' : '')
				. ($this->namespace ? $this->namespace->unresolveName($this->returnType) : $this->returnType) : '')
			. ($this->abstract || $this->body === NULL
				? ';'
				: ($this->getName() ? "\n" : ' ') . "{\n" . Nette\Utils\Strings::indent(ltrim(rtrim($this->body) . "\n"), 1) . '}');
	}


	/**
	 * @param  Parameter[]
	 * @return static
	 */
	public function setParameters(array $val)
	{
		$this->parameters = [];
		foreach ($val as $v) {
			if (!$v instanceof Parameter) {
				throw new Nette\InvalidArgumentException('Argument must be Nette\PhpGenerator\Parameter[].');
			}
			$this->parameters[$v->getName()] = $v;
		}
		return $this;
	}


	/**
	 * @return Parameter[]
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}


	/**
	 * @param  string  without $
	 */
	public function addParameter(string $name, $defaultValue = NULL): Parameter
	{
		$param = new Parameter($name);
		if (func_num_args() > 1) {
			$param->setDefaultValue($defaultValue);
		}
		return $this->parameters[$name] = $param;
	}


	/**
	 * @return static
	 */
	public function setUses(array $val)
	{
		$this->uses = $val;
		return $this;
	}


	public function getUses(): array
	{
		return $this->uses;
	}


	public function addUse($name): Parameter
	{
		return $this->uses[] = new Parameter($name);
	}


	/**
	 * @param  string|NULL
	 * @return static
	 */
	public function setBody($code, array $args = NULL)
	{
		$this->body = $args === NULL ? $code : Helpers::formatArgs($code, $args);
		return $this;
	}


	/**
	 * @return string|NULL
	 */
	public function getBody()
	{
		return $this->body;
	}


	/**
	 * @return static
	 */
	public function addBody(string $code, array $args = NULL)
	{
		$this->body .= ($args === NULL ? $code : Helpers::formatArgs($code, $args)) . "\n";
		return $this;
	}


	/**
	 * @return static
	 */
	public function setStatic(bool $val)
	{
		$this->static = $val;
		return $this;
	}


	public function isStatic(): bool
	{
		return $this->static;
	}


	/**
	 * @return static
	 */
	public function setFinal(bool $val)
	{
		$this->final = $val;
		return $this;
	}


	public function isFinal(): bool
	{
		return $this->final;
	}


	/**
	 * @return static
	 */
	public function setAbstract(bool $val)
	{
		$this->abstract = $val;
		return $this;
	}


	public function isAbstract(): bool
	{
		return $this->abstract;
	}


	/**
	 * @return static
	 */
	public function setReturnReference(bool $val)
	{
		$this->returnReference = $val;
		return $this;
	}


	public function getReturnReference(): bool
	{
		return $this->returnReference;
	}


	/**
	 * @return static
	 */
	public function setReturnNullable(bool $val)
	{
		$this->returnNullable = $val;
		return $this;
	}


	public function getReturnNullable(): bool
	{
		return $this->returnNullable;
	}


	/**
	 * @return static
	 */
	public function setVariadic(bool $val)
	{
		$this->variadic = $val;
		return $this;
	}


	public function isVariadic(): bool
	{
		return $this->variadic;
	}


	/**
	 * @return static
	 */
	public function setNamespace(PhpNamespace $val = NULL)
	{
		$this->namespace = $val;
		return $this;
	}


	/**
	 * @param  string|NULL
	 * @return static
	 */
	public function setReturnType($val)
	{
		$this->returnType = $val ? (string) $val : NULL;
		return $this;
	}


	/**
	 * @return string|NULL
	 */
	public function getReturnType()
	{
		return $this->returnType;
	}

}
