<template>
    <div class="beacon-fieldtype">
        <!-- Master enable toggle -->
        <div class="beacon-fieldtype__header">
            <label class="beacon-toggle">
                <input
                    type="checkbox"
                    v-model="value.enabled"
                    class="beacon-toggle__input"
                />
                <span class="beacon-toggle__label">
                    {{ __('beacon::fieldtype.enable_label') }}
                </span>
            </label>
        </div>

        <div v-if="value.enabled" class="beacon-fieldtype__body">
            <!-- Override collection rule notice -->
            <div v-if="hasCollectionRule" class="beacon-fieldtype__notice">
                <label class="beacon-toggle beacon-toggle--small">
                    <input
                        type="checkbox"
                        v-model="value.override_collection"
                        class="beacon-toggle__input"
                    />
                    <span class="beacon-toggle__label">
                        {{ __('beacon::fieldtype.override_collection') }}
                    </span>
                </label>
            </div>

            <!-- Notification type selector -->
            <div class="beacon-fieldtype__row">
                <label class="beacon-fieldtype__label">
                    {{ __('beacon::fieldtype.type_label') }}
                </label>
                <select v-model="value.type" class="beacon-fieldtype__select">
                    <option value="announcement">{{ __('beacon::fieldtype.type_announcement') }}</option>
                    <option value="discount">{{ __('beacon::fieldtype.type_discount') }}</option>
                    <option value="cta">{{ __('beacon::fieldtype.type_cta') }}</option>
                    <option value="consent">{{ __('beacon::fieldtype.type_consent') }}</option>
                </select>
            </div>

            <!-- Announcement fields -->
            <div v-if="value.type === 'announcement'" class="beacon-fieldtype__type-fields">
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                        {{ __('beacon::fieldtype.announcement_message') }}
                    </label>
                    <textarea
                        v-model="value.payload.message"
                        class="beacon-fieldtype__textarea"
                        :placeholder="__('beacon::fieldtype.announcement_message_placeholder')"
                        rows="3"
                        required
                    />
                </div>
                <div class="beacon-fieldtype__row-group">
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label">
                            {{ __('beacon::fieldtype.cta_label') }}
                        </label>
                        <input
                            type="text"
                            v-model="value.payload.cta_label"
                            class="beacon-fieldtype__input"
                            :placeholder="__('beacon::fieldtype.cta_label_placeholder')"
                        />
                    </div>
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label">
                            {{ __('beacon::fieldtype.cta_url') }}
                        </label>
                        <input
                            type="url"
                            v-model="value.payload.cta_url"
                            class="beacon-fieldtype__input"
                            :placeholder="__('beacon::fieldtype.cta_url_placeholder')"
                        />
                    </div>
                </div>
            </div>

            <!-- Discount code fields -->
            <div v-if="value.type === 'discount'" class="beacon-fieldtype__type-fields">
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                        {{ __('beacon::fieldtype.discount_message') }}
                    </label>
                    <textarea
                        v-model="value.payload.message"
                        class="beacon-fieldtype__textarea"
                        rows="3"
                        required
                    />
                </div>
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                        {{ __('beacon::fieldtype.discount_code') }}
                    </label>
                    <input
                        type="text"
                        v-model="value.payload.code"
                        class="beacon-fieldtype__input beacon-fieldtype__input--code"
                        placeholder="SAVE20"
                        required
                    />
                </div>
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.discount_code_label') }}
                    </label>
                    <input
                        type="text"
                        v-model="value.payload.code_label"
                        class="beacon-fieldtype__input"
                        :placeholder="__('beacon::fieldtype.discount_code_label_placeholder')"
                    />
                </div>
                <div class="beacon-fieldtype__row">
                    <label class="beacon-toggle beacon-toggle--small">
                        <input
                            type="checkbox"
                            v-model="value.payload.show_countdown"
                            class="beacon-toggle__input"
                        />
                        <span class="beacon-toggle__label">
                            {{ __('beacon::fieldtype.discount_show_countdown') }}
                        </span>
                    </label>
                </div>
                <div v-if="value.payload.show_countdown" class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                        {{ __('beacon::fieldtype.discount_countdown_until') }}
                    </label>
                    <input
                        type="datetime-local"
                        v-model="value.payload.countdown_until"
                        class="beacon-fieldtype__input"
                    />
                </div>
            </div>

            <!-- CTA Banner fields -->
            <div v-if="value.type === 'cta'" class="beacon-fieldtype__type-fields">
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                        {{ __('beacon::fieldtype.cta_headline') }}
                    </label>
                    <input
                        type="text"
                        v-model="value.payload.headline"
                        class="beacon-fieldtype__input"
                        required
                    />
                </div>
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.cta_description') }}
                    </label>
                    <textarea
                        v-model="value.payload.description"
                        class="beacon-fieldtype__textarea"
                        rows="2"
                    />
                </div>
                <div class="beacon-fieldtype__row-group">
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                            {{ __('beacon::fieldtype.cta_primary_label') }}
                        </label>
                        <input
                            type="text"
                            v-model="value.payload.primary_label"
                            class="beacon-fieldtype__input"
                            required
                        />
                    </div>
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                            {{ __('beacon::fieldtype.cta_primary_url') }}
                        </label>
                        <input
                            type="url"
                            v-model="value.payload.primary_url"
                            class="beacon-fieldtype__input"
                            required
                        />
                    </div>
                </div>
                <div class="beacon-fieldtype__row-group beacon-fieldtype__row-group--optional">
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label">
                            {{ __('beacon::fieldtype.cta_secondary_label') }}
                        </label>
                        <input
                            type="text"
                            v-model="value.payload.secondary_label"
                            class="beacon-fieldtype__input"
                        />
                    </div>
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label">
                            {{ __('beacon::fieldtype.cta_secondary_url') }}
                        </label>
                        <input
                            type="url"
                            v-model="value.payload.secondary_url"
                            class="beacon-fieldtype__input"
                        />
                    </div>
                </div>
            </div>

            <!-- Consent notice fields -->
            <div v-if="value.type === 'consent'" class="beacon-fieldtype__type-fields">
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                        {{ __('beacon::fieldtype.consent_message') }}
                    </label>
                    <textarea
                        v-model="value.payload.message"
                        class="beacon-fieldtype__textarea"
                        rows="3"
                        required
                    />
                </div>
                <div class="beacon-fieldtype__row-group">
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                            {{ __('beacon::fieldtype.consent_accept_label') }}
                        </label>
                        <input
                            type="text"
                            v-model="value.payload.accept_label"
                            class="beacon-fieldtype__input"
                            :placeholder="__('beacon::fieldtype.consent_accept_placeholder')"
                            required
                        />
                    </div>
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label beacon-fieldtype__label--required">
                            {{ __('beacon::fieldtype.consent_decline_label') }}
                        </label>
                        <input
                            type="text"
                            v-model="value.payload.decline_label"
                            class="beacon-fieldtype__input"
                            :placeholder="__('beacon::fieldtype.consent_decline_placeholder')"
                            required
                        />
                    </div>
                </div>
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.consent_hook') }}
                    </label>
                    <input
                        type="text"
                        v-model="value.payload.consent_hook"
                        class="beacon-fieldtype__input beacon-fieldtype__input--code"
                        placeholder="window.gtag"
                    />
                    <p class="beacon-fieldtype__help">
                        {{ __('beacon::fieldtype.consent_hook_help') }}
                    </p>
                </div>
            </div>

            <!-- Display settings -->
            <div class="beacon-fieldtype__section">
                <h4 class="beacon-fieldtype__section-title">
                    {{ __('beacon::fieldtype.display_settings') }}
                </h4>

                <!-- Position -->
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.position_label') }}
                    </label>
                    <div class="beacon-position-grid">
                        <label
                            v-for="pos in positions"
                            :key="pos.value"
                            class="beacon-position-option"
                            :class="{ 'beacon-position-option--active': value.position === pos.value }"
                        >
                            <input
                                type="radio"
                                :value="pos.value"
                                v-model="value.position"
                                class="sr-only"
                            />
                            <span class="beacon-position-option__icon" v-html="pos.icon" />
                            <span class="beacon-position-option__label">{{ pos.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Trigger -->
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.trigger_label') }}
                    </label>
                    <select v-model="value.trigger" class="beacon-fieldtype__select">
                        <option value="immediate">{{ __('beacon::fieldtype.trigger_immediate') }}</option>
                        <option value="delay">{{ __('beacon::fieldtype.trigger_delay') }}</option>
                        <option value="scroll">{{ __('beacon::fieldtype.trigger_scroll') }}</option>
                        <option value="exit_intent">{{ __('beacon::fieldtype.trigger_exit_intent') }}</option>
                    </select>
                </div>
                <div v-if="value.trigger === 'delay'" class="beacon-fieldtype__row beacon-fieldtype__row--indent">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.trigger_delay_seconds') }}
                    </label>
                    <input
                        type="number"
                        v-model.number="value.trigger_value"
                        class="beacon-fieldtype__input beacon-fieldtype__input--narrow"
                        min="0"
                        max="300"
                        :placeholder="defaultDelay"
                    />
                </div>
                <div v-if="value.trigger === 'scroll'" class="beacon-fieldtype__row beacon-fieldtype__row--indent">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.trigger_scroll_percent') }}
                    </label>
                    <input
                        type="number"
                        v-model.number="value.trigger_value"
                        class="beacon-fieldtype__input beacon-fieldtype__input--narrow"
                        min="0"
                        max="100"
                        placeholder="50"
                    />
                </div>

                <!-- Frequency -->
                <div class="beacon-fieldtype__row">
                    <label class="beacon-fieldtype__label">
                        {{ __('beacon::fieldtype.frequency_label') }}
                    </label>
                    <select v-model="value.frequency" class="beacon-fieldtype__select">
                        <option value="always">{{ __('beacon::fieldtype.frequency_always') }}</option>
                        <option value="session">{{ __('beacon::fieldtype.frequency_session') }}</option>
                        <option value="daily">{{ __('beacon::fieldtype.frequency_daily') }}</option>
                        <option value="permanent">{{ __('beacon::fieldtype.frequency_permanent') }}</option>
                        <option value="dismissed">{{ __('beacon::fieldtype.frequency_dismissed') }}</option>
                    </select>
                </div>

                <!-- Schedule -->
                <div class="beacon-fieldtype__row-group">
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label">
                            {{ __('beacon::fieldtype.active_from') }}
                        </label>
                        <input
                            type="datetime-local"
                            v-model="value.active_from"
                            class="beacon-fieldtype__input"
                        />
                    </div>
                    <div class="beacon-fieldtype__row">
                        <label class="beacon-fieldtype__label">
                            {{ __('beacon::fieldtype.active_until') }}
                        </label>
                        <input
                            type="datetime-local"
                            v-model="value.active_until"
                            class="beacon-fieldtype__input"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BeaconNotificationFieldtype',

    mixins: [Fieldtype],

    inject: {
        storeName: { default: null },
    },

    data() {
        return {
            value: this.initValue(),
            hasCollectionRule: false,
        };
    },

    computed: {
        defaultDelay() {
            return window.Beacon?.config?.defaultDelay ?? 2;
        },

        positions() {
            return [
                {
                    value: 'top-bar',
                    label: this.__('beacon::fieldtype.position_top_bar'),
                    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><rect x="2" y="3" width="20" height="4" rx="1" fill="currentColor"/></svg>',
                },
                {
                    value: 'bottom-bar',
                    label: this.__('beacon::fieldtype.position_bottom_bar'),
                    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><rect x="2" y="17" width="20" height="4" rx="1" fill="currentColor"/></svg>',
                },
                {
                    value: 'bottom-right',
                    label: this.__('beacon::fieldtype.position_bottom_right'),
                    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><rect x="13" y="13" width="9" height="8" rx="1" fill="currentColor"/></svg>',
                },
                {
                    value: 'bottom-left',
                    label: this.__('beacon::fieldtype.position_bottom_left'),
                    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><rect x="2" y="13" width="9" height="8" rx="1" fill="currentColor"/></svg>',
                },
                {
                    value: 'modal-center',
                    label: this.__('beacon::fieldtype.position_modal_center'),
                    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><rect x="5" y="7" width="14" height="10" rx="1" fill="currentColor"/></svg>',
                },
            ];
        },
    },

    watch: {
        value: {
            deep: true,
            handler(val) {
                this.update(val);
            },
        },

        'value.type'(newType) {
            // Reset payload when switching types to avoid stale data
            this.value.payload = {};
        },
    },

    methods: {
        initValue() {
            const defaults = {
                enabled: false,
                type: 'announcement',
                position: 'bottom-right',
                trigger: 'immediate',
                trigger_value: null,
                frequency: 'session',
                active_from: null,
                active_until: null,
                override_collection: false,
                payload: {},
            };

            return Object.assign({}, defaults, this.value ?? {});
        },
    },
};
</script>
