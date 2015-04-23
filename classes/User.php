<?php

	class User {
		private $_db,
				$_data,
				$_sessionName,
				$_cookieName,
				$_isLoggedIn;


		public function __construct($user = null) {
			$this->_db = DB::getInstance();

			$this->_sessionName = Config::get('session/session_name');
			$this->_cookieName = Config::get('remember/cookie_name');

			if (!$user) {
				if (Session::exists($this->_sessionName)) {
					$user = Session::get($this->_sessionName);

					if ($this->find($user)) {
						$this->_isLoggedIn = true;
					} else {
						//process logout
					}
				}

			} else {
				$this->find($user);
			}
		}

		public function update($fields = array(), $id = null) {

			if (!$id && $this->isLoggedIn()) {
				$id = $this->data()->id;
			}

			if (!$this->_db->update('users', $id, $fields)) {
				throw new Exception('There was a problem updating.');
			}
		}

		public function create($fields = array()) {
			if (!$this->_db->insert('users', $fields)) {
				throw new Exception('There was a problem creating an account.');
			}
		}

		public function find($email = null) {
			if ($email) {
				$field = (is_numeric($email)) ? 'id' : 'email';
				$data = $this->_db->get('users', array($field, '=', $email));

				if ($data->count()) {
					$this->_data = $data->first();
					return true;
				}
			}
			return false;
		}

		public function logout() {
			$this->_db->delete('users_session', array('user_id', '=', $this->data()->id));

			Session::delete($this->_sessionName);
			Cookie::delete($this->_cookieName);
		}

		public function login($email = null, $password = null, $remember = false) {

			if (!$email && !$password && $this->exists()) {
				Session::put($this->_sessionName, $this->data()->id);
			} else {
				$user = $this->find($email);

				if ($user) {
					if ($this->data()->password === Hash::make($password, $this->data()->salt)) {
						Session::put($this->_sessionName, $this->data()->id);

						$this->_db->insert('ip_log', array(
							'user' => $this->_data->id,
							'ip' => $_SERVER['REMOTE_ADDR'],
							'time' => date('Y-m-d H:i:s')
						));

						if ($remember) {
							$hash = Hash::unique();
							$hashCheck = $this->_db->get('users_session',array('user_id', '=', $this->data()->id));

							if (!$hashCheck->count()) {
								$this->_db->insert('users_session', array(
									'user_id' => $this->data()->id,
									'hash' => $hash
								));
							} else {
								$hash = $hashCheck->first()->hash;
							}
							Cookie::put($this->_cookieName, $hash, Config::get('remember/cookie_expiry'));
						}
						return true;
					}
				}
			}
			return false;
		}

		public function hasPermission($key) {
			$group = $this->_db->get('groups', array('id', '=', $this->data()->group));

			if ($group->count()) {
				$permissions = json_decode($group->first()->permissions, true);

				if ($permissions[$key] == true) {
					return true;
				}
			}
			return false;
		}

		public function exists() {
			return (!empty($this->_data)) ? true : false;
		}

		public function data() {
			return $this->_data;

		}

		public function isLoggedIn() {
			return $this->_isLoggedIn;
		}

		public function isAdmin() {
			return ($this->_data->group == 1) ? true : false;
		}

		public static function all() {
			$db = DB::getInstance();
			$db->getAll('users');

			return $db->results();
		}

		public static function nameFromId($id){
			$db = DB::getInstance();
			$db->get('users', array('id', '=', $id));

			return $db->first()->fullname;
		}

	}

?>
