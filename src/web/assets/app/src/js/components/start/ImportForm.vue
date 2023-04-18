<template>
    <start-form action="zen/import" icon="import" button-text="Import Content" @submit="onSubmit">
        <template #header>
            <h1>{{ t('zen', 'Import Content') }}</h1>
            <markdown class="zui-intro-text" :source="t('zen', 'To import content **into this** site, select the export file from your other install. You\'ll be directed to the configuration section.')" />
        </template>

        <template #body>
            <div :id="getId('file', 'field')" class="field" :data-attribute="getId('file')">
                <div class="heading">
                    <label :id="getId('file', 'label')" :for="getId('file')">
                        {{ t('zen', 'Import File') }}<span class="visually-hidden">{{ t('app', 'Required') }}</span><span class="required" aria-hidden="true"></span>
                    </label>
                </div>

                <div :id="getId('file', 'instructions')" class="instructions">
                    <markdown :source="t('zen', 'Upload the `.zip` file that‘s been exported from Zen on another install.')" />
                </div>

                <div class="input">
                    <input :id="getId('file')" type="file" name="file" :aria-describedby="getId('file', 'instructions')" accept=".zip">
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
    name: 'ImportForm',

    components: {
        Markdown,
        StartForm,
    },

    data() {
        return {
            fileId: Craft.randomString(10),
        };
    },

    methods: {
        getId(prefix, suffix) {
            return [prefix, this[`${prefix}Id`], suffix].filter((n) => {
                return n;
            }).join('-');
        },

        onSubmit(response) {
            this.$router.push({ path: `/import/configure/${response.data.filename}` });
        },
    },
};

</script>

<style lang="scss">

[type=file] {
    overflow: hidden;
    border-width: 1px;
    border-radius: 4px;
    width: 100%;
}

[type=file]:not(:disabled):not([readonly]) {
    cursor: pointer;
}

[type=file]::file-selector-button {
    padding: 0.5rem 0.75rem;
    margin-right: 0.75rem;
    pointer-events: none;
    border-color: #d8dee7;
    border-style: solid;
    border-width: 0;
    border-inline-end-width: 1px;
    border-radius: 3px 0 0 3px;
    background-color: #e2e8f0;
}

[type=file]::-webkit-file-upload-button {
    padding: 0.5rem 0.75rem;
    margin-right: 0.75rem;
    pointer-events: none;
    border-color: #d8dee7;
    border-style: solid;
    border-width: 0;
    border-inline-end-width: 1px;
    border-radius: 3px 0 0 3px;
    background-color: #e2e8f0;
}

[type=file]:hover:not(:disabled):not([readonly])::file-selector-button {
    background-color: #d0d7e2;
}

[type=file]:hover:not(:disabled):not([readonly])::-webkit-file-upload-button {
    background-color: #d0d7e2;
}

</style>
