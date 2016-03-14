<?php

namespace Dlnsk\HierarchicalRBAC;


class ArrayAuthorization
{
	public function getPermissions() {
		return [];
	}

	public function getRoles() {
		return [];
	}


	/**
	 * Checking permission for choosed user
	 *
	 * @return boolean
	 */
	public function checkPermission($user, $ability, $arguments)
	{
		\Debugbar::info($user->id.' - '.$ability);
		if ($user->role === 'admin') {
			return true;
		}

		// У пользователя роль, которой нет в списке
		$roles = $this->getRoles();
		if (!isset($roles[$user->role])) {
			return null;
		}
		\Debugbar::info('Роль опознана');

		// Ищем разрешение для данной роли среди наследников текущего разрешения
		$role = $roles[$user->role];
		$permissions = $this->getPermissions();
		$current = $ability;
		// Если для разрешения указана замена - элемент 'equal', то проверяется замена
		// (только при наличии оригинального разрешения в роли).
		// Callback оригинального не вызывается.
		if (in_array($current, $role) and isset($permissions[$current]['equal'])) {
			$current = $permissions[$current]['equal'];
			\Debugbar::info('Разрешение равно '.$current);
		}
		while (!in_array($current, $role) and isset($permissions[$current]['next'])) {
			$current = $permissions[$current]['next'];
			\Debugbar::info($current);
		}
		if (!in_array($current, $role)) {
			// Ни одного подходящего разрешения небыло найдено
			\Debugbar::info('Разрешение '.$current.' не подходящее');
			return null;
		}

		$methods = get_class_methods($this);
		$method = camel_case($current);
		if (in_array($method, $methods)) {
			// Преобразуем массив в единичный элемент если он содержит один элемент
			// или это ассоциативный массив с любым кол-вом элементов
			if (!empty($arguments)) {
				$arg = (count($arguments) > 1 or array_keys($arguments)[0] !== 0) ? $arguments : last($arguments);
			} else {
				$arg = null;
			}
			return $this->$method($user, $arg, $ability) ? true : null;
		}
		return true;
	}


	/**
	 * Return model of given class or exception if can't
	 *
	 * @param  class 			$class 		This is a class which instance we need.
	 * @param  Model|indeger 	$id 		Instance or its ID
	 *
	 * @return Model|exception
	 */
	public function getModel($class, $id)
	{
		if ($id instanceof $class) {
			return $id;
		} elseif (ctype_digit(strval($id))) { // целое число в виде числа или текстовой строки
			return $class::findOrFail($id);
		} else {
			//TODO: Использовать свое исключение
			throw new \Exception("Can't get model.", 1);
		}
	}

}
