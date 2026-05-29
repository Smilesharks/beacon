import BeaconNotificationFieldtype from './components/fieldtypes/BeaconNotificationFieldtype.vue';

Statamic.booting(() => {
    Statamic.$components.register('beacon-notification-fieldtype', BeaconNotificationFieldtype);
});
