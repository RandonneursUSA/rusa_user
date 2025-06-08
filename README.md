# rusa_user
Drupal custom module for rusa.org

This module provides a framework for creating and managing Drupal users from RUSA members.

src/Form/RusaUserForm
    replaces the default Drupal account creation form.
    accounts are created using RUSA member data
    
src/Plugin/Menu/RusaAccountMenu
    changes the login link to Member Login
    
src/Routing/RoutSubscriber
    alters the user.register route to point to our RusaUserForm
    
src/RusaUserManager
    synchronizes certain data from GDBM to Drupal
    called from rusa_user.module at login and cron
    
src/Form/RusaUserSettingsForm
    provides a settings form wth configurable message fields
    saved to config as rusa_user.settings

rusa_user.links.menu.yml
    adds the settings form to the Configuration -> People menu
    
rusa_user.routing.yml
    defines routes

rusa_user.module
    numerous old style hooks that might not be the best way to do it
        function rusa_user_help($route_name, RouteMatchInterface $route_match) {
        function rusa_user_entity_presave(EntityInterface $entity) {
        function rusa_user_form_user_login_form_alter(&$form, FormStateInterface $form_state) {
        function rusa_user_user_login($account) {
        function _rusa_user_user_login_form_validate(&$form, FormStateInterface $form_state) {
        function rusa_user_mail($key, &$message, $params) {
        function rusa_user_menu_links_discovered_alter(&$links) {
        function rusa_user_cron() {
    
    
    
@To-do
These can be removed
src/Controller/RusaUserController
src/EventSubscriber/RusaUserEventSubscriber

also must remove route to the controller

Move the rusa_user_user_login_form_validate code from rusa_user.module to RusaUserManager
