<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;

use Nette;


/**
 * Creates a representation based on reflection.
 */
class Factory
{
	use Nette\SmartObject;

	/**
	 * @return ClassType
	 */
	public function fromClassReflection(\ReflectionClass $from)
	{
		$class = $from->isAnonymous()
			? new ClassType
			: new ClassType($from->getShortName(), new PhpNamespace($from->getNamespaceName()));
		$class->setType($from->isInterface() ? 'interface' : ($from->isTrait() ? 'trait' : 'class'));
		$class->setFinal($from->isFinal() && $class->getType() === 'class');
		$class->setAbstract($from->isAbstract() && $class->getType() === 'class');
		$class->setImplements($from->getInterfaceNames());
		$class->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		if ($from->getParentClass()) {
			$class->setExtends($from->getParentClass()->getName());
			$class->setImplements(array_diff($class->getImplements(), $from->getParentClass()->getInterfaceNames()));
		}
		$props = $methods = [];
		foreach ($from->getProperties() as $prop) {
			if ($prop->isDefault() && $prop->getDeclaringClass()->getName() === $from->getName()) {
				$props[$prop->getName()] = $this->fromPropertyReflection($prop);
			}
		}
		$class->setProperties($props);
		foreach ($from->getMethods() as $method) {
			if ($method->getDeclaringClass()->getName() === $from->getName()) {
				$methods[$method->getName()] = $this->fromFunctionReflection($method)->setNamespace($class->getNamespace());
			}
		}
		$class->setMethods($methods);
		return $class;
	}


	/**
	 * @return Method
	 */
	public function fromFunctionReflection(\ReflectionFunctionAbstract $from)
	{
		$method = new Method($from->isClosure() ? NULL : $from->getName());
		$params = [];
		foreach ($from->getParameters() as $param) {
			$params[$param->getName()] = $this->fromParameterReflection($param);
		}
		$method->setParameters($params);
		if ($from instanceof \ReflectionMethod) {
			$isInterface = $from->getDeclaringClass()->isInterface();
			$method->setStatic($from->isStatic());
			$method->setVisibility($from->isPrivate() ? 'private' : ($from->isProtected() ? 'protected' : ($isInterface ? NULL : 'public')));
			$method->setFinal($from->isFinal());
			$method->setAbstract($from->isAbstract() && !$isInterface);
			$method->setBody($from->isAbstract() ? FALSE : '');
		}
		$method->setReturnReference($from->returnsReference());
		$method->setVariadic($from->isVariadic());
		$method->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		if ($from->hasReturnType()) {
			$method->setReturnType((string) $from->getReturnType());
			$method->setReturnNullable($from->getReturnType()->allowsNull());
		}
		return $method;
	}


	/**
	 * @return Parameter
	 */
	public function fromParameterReflection(\ReflectionParameter $from)
	{
		$param = new Parameter($from->getName());
		$param->setReference($from->isPassedByReference());
		$param->setTypeHint($from->hasType() ? (string) $from->getType() : NULL);
		$param->setNullable($from->hasType() && $from->getType()->allowsNull());
		if ($from->isDefaultValueAvailable()) {
			$param->setOptional(TRUE);
			$param->setDefaultValue($from->isDefaultValueConstant()
				? new PhpLiteral($from->getDefaultValueConstantName())
				: $from->getDefaultValue());
			$param->setNullable($param->isNullable() && $param->getDefaultValue() !== NULL);
		}
		return $param;
	}


	/**
	 * @return Property
	 */
	public function fromPropertyReflection(\ReflectionProperty $from)
	{
		$prop = new Property($from->getName());
		$prop->setValue($from->getDeclaringClass()->getDefaultProperties()[$prop->getName()] ?? NULL);
		$prop->setStatic($from->isStatic());
		$prop->setVisibility($from->isPrivate() ? 'private' : ($from->isProtected() ? 'protected' : 'public'));
		$prop->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		return $prop;
	}

}
