<template>
    <start-form action="zen/export" icon="export" button-text="Export Content" response-type="blob" @submit="onSubmit">
        <template #header>
            <h1>{{ t('zen', 'Export Content') }}</h1>
            <markdown class="zui-intro-text" :source="t('zen', 'To export content **from this** site, select the date range to export content for. Once exported, you\'ll be able to import it on another install.')" />
        </template>

        <template #body>
            <div :id="getId('elements', 'field')" class="field" :data-attribute="getId('elements')">
                <div class="heading">
                    <label :id="getId('elements', 'label')" :for="getId('elements')">
                        {{ t('zen', 'Elements') }}<span class="visually-hidden">{{ t('app', 'Required') }}</span><span class="required" aria-hidden="true"></span>
                    </label>
                </div>

                <div :id="getId('elements', 'instructions')" class="instructions">
                    <markdown :source="t('zen', 'Select which elements to export.')" />
                </div>

                <div class="input">
                    <element-combobox :loading="loading" :items="elementOptions" />
                </div>
            </div>

            <div :id="getId('dateRange', 'field')" class="field" :data-attribute="getId('dateRange')">
                <div class="heading">
                    <label :id="getId('dateRange', 'label')" :for="getId('dateRange')">
                        {{ t('zen', 'Date Range') }}<span class="visually-hidden">{{ t('app', 'Required') }}</span><span class="required" aria-hidden="true"></span>
                    </label>
                </div>

                <div :id="getId('dateRange', 'instructions')" class="instructions">
                    <markdown :source="t('zen', 'Select the date range to export content for.')" />
                </div>

                <div class="input">
                    <div class="flex">
                        <div class="datetimewrapper">
                            <div class="datewrapper">
                                <input
                                    :id="getId('fromDate')"
                                    type="text"
                                    class="text"
                                    name="fromDate[date]"
                                    size="10"
                                    autocomplete="off"
                                    placeholder=" "
                                    dir="ltr"
                                    :value="getDate(fromDate)"
                                >

                                <div data-icon="date"></div>
                                <input type="hidden" name="fromDate[timezone]" :value="timezone">
                            </div>
                        </div>

                        <span>{{ t('zen', 'to') }}</span>

                        <div class="datetimewrapper">
                            <div class="datewrapper">
                                <input
                                    :id="getId('toDate')"
                                    type="text"
                                    class="text"
                                    name="toDate[date]"
                                    size="10" autocomplete="off"
                                    placeholder=" "
                                    dir="ltr"
                                    :value="getDate(toDate)"
                                >

                                <div data-icon="date"></div>
                                <input type="hidden" name="toDate[timezone]" :value="timezone">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </start-form>
</template>

<script>
import { getId } from '@utils/string';

import StartForm from '@components/start/StartForm.vue';
import Markdown from '@components/Markdown.vue';

export default {
    name: 'ExportForm',

    components: {
        Markdown,
        StartForm,
    },

    data() {
        return {
            loading: false,
            fromDate: new Date((new Date()).setMonth((new Date()).getMonth() - 1)),
            toDate: new Date(),
            timezone: Craft.timezone,
            elementsId: Craft.randomString(10),
            dateRangeId: Craft.randomString(10),
            fromDateId: Craft.randomString(10),
            toDateId: Craft.randomString(10),
            elementOptions: [],
        };
    },

    mounted() {
        this.$nextTick(() => {
            this.updateElements();

            $(this.$el).find(`#${this.getId('fromDate')}`).datepicker($.extend({
                defaultDate: this.fromDate,
                onSelect: (date) => {
                    this.fromDate = new Date(date);

                    this.updateElements();
                },
            }, Craft.datepickerOptions));

            $(this.$el).find(`#${this.getId('toDate')}`).datepicker($.extend({
                defaultDate: this.toDate,
                onSelect: (date) => {
                    this.toDate = new Date(date);

                    this.updateElements();
                },
            }, Craft.datepickerOptions));
        });
    },

    methods: {
        getId(prefix, suffix) {
            return [prefix, this[`${prefix}Id`], suffix].filter((n) => {
                return n;
            }).join('-');
        },

        getDate(value) {
            return Craft.formatDate(value);
        },

        updateElements() {
            const fromDate = new Date(`${this.fromDate} UTC`).toISOString().split('T')[0];
            const toDate = new Date(`${this.toDate} UTC`).toISOString().split('T')[0];

            if (!fromDate || !toDate) {
                return;
            }

            const data = {
                fromDate,
                toDate,
            };

            this.loading = true;

            Craft.sendActionRequest('POST', 'zen/export/get-element-options', { data })
                .then((response) => {
                    this.elementOptions = response.data.options;
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        onSubmit(response) {
            const header = response.headers['content-disposition'];
            const parts = header.split(';');

            const a = document.createElement('a');
            a.href = window.URL.createObjectURL(response.data);
            a.download = parts[1].split('=')[1].replaceAll('"', '');
            document.body.appendChild(a);
            a.click();
            a.remove();
        },
    },
};

</script>
