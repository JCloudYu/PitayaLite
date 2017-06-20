<?php
	final class PBScriptCtrl {
		public static function Imprint($____path_of_the_file_to_be_imprinted = '')
		{
			$____pre_cached_to_be_deleted_existing_variables = get_defined_vars();

			if (!is_string($____path_of_the_file_to_be_imprinted) ||
				empty($____path_of_the_file_to_be_imprinted))
				return NULL;

			require $____path_of_the_file_to_be_imprinted;
			$____path_of_the_file_to_be_imprinted = get_defined_vars();

			foreach ($____pre_cached_to_be_deleted_existing_variables as $varName => $varValue)
				unset($____path_of_the_file_to_be_imprinted[$varName]);

			return $____path_of_the_file_to_be_imprinted;
		}
		public static function Script($____path_of_the_script_to_be_executed, $____parameters_used_in_the_executed_script = array(), &$____script_defined_variables = NULL)
		{
			$____pre_cached_to_be_deleted_existing_variables = get_defined_vars();

			if (!is_string($____path_of_the_script_to_be_executed) || empty($____path_of_the_script_to_be_executed))
				return '';


			ob_start();
			extract($____parameters_used_in_the_executed_script);
			require $____path_of_the_script_to_be_executed;
			$____output_buffer_generated_by_executed_script = ob_get_clean();
			$____variables_that_are_used_in_executed_script = get_defined_vars();



			foreach ($____pre_cached_to_be_deleted_existing_variables as $varName => $varValue)
				unset($____variables_that_are_used_in_executed_script[$varName]);

			foreach ($____parameters_used_in_the_executed_script as $varName => $varValue)
				unset($____variables_that_are_used_in_executed_script[$varName]);


			$____script_defined_variables = $____variables_that_are_used_in_executed_script;
			return $____output_buffer_generated_by_executed_script;
		}
		public static function ScriptOut($____path_of_the_script_to_be_executed, $____parameters_used_in_the_executed_script = array(), &$____script_defined_variables = NULL)
		{
			$____pre_cached_to_be_deleted_existing_variables = get_defined_vars();

			if (!is_string($____path_of_the_script_to_be_executed) || empty($____path_of_the_script_to_be_executed)) return;


			extract($____parameters_used_in_the_executed_script);
			require $____path_of_the_script_to_be_executed;
			$____variables_that_are_used_in_executed_script = get_defined_vars();



			foreach ($____pre_cached_to_be_deleted_existing_variables as $varName => $varValue)
				unset($____variables_that_are_used_in_executed_script[$varName]);

			foreach ($____parameters_used_in_the_executed_script as $varName => $varValue)
				unset($____variables_that_are_used_in_executed_script[$varName]);


			$____script_defined_variables = $____variables_that_are_used_in_executed_script;
		}
	}
