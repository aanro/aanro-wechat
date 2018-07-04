<style scoped>
</style>

<template>

    <someline-form-panel
            @form-submit="onFormSubmit"
            v-loading.body="isLoading"
    >
        <template slot="PanelHeading">
            Someline 文章
        </template>

        <someline-form-group-input
                placeholder="标题"
                :rounded="true"
                v-model="form_data.title"
                :required="true"
        >
            <template slot="Label">标题</template>
        </someline-form-group-input>
        <someline-form-group-line/>

        <someline-form-group-editor
                height="500px"
                map-ak="F300593dbf91cd7f8890e52370fa0006"
                :log="true"
                :wang-image-upload="true"
                v-model="form_data.body_html"
                @editor-config="onEditorConfig"
                @editor-ready="onEditorReady"
                @editor-change="onEditorChange"
        >
            <template slot="Label">内容</template>
        </someline-form-group-editor>
        <someline-form-group-line/>

        <someline-form-group-switch-list
                name="example_switch"
                :items="single_checkbox_items"
                v-model="form_data.pinned">
            <template slot="Label">置顶</template>
            <template slot="HelpText">是否置顶文章</template>
        </someline-form-group-switch-list>
        <someline-form-group-line/>

        <someline-form-group>
            <template slot="ControlArea">
                <button type="submit" class="btn btn-primary">保存</button>
            </template>
            <pre class="m-t-sm m-b-none">{{ form_data }}</pre>
        </someline-form-group>

    </someline-form-panel>

</template>

<script>
    export default{
        props: [],
        data(){
            return {

                isLoading: false,

                single_checkbox_items: [
                    {
                        text: '置顶',
                        value: 'yes',
                    }
                ],

                editor: null,

                form_data: {
                    title: null,
                    pinned: false,

                    body_html: null,
                    body_text: null,
                },

            }
        },
        computed: {},
        components: {},
        http: {
            root: '/api',
            headers: {
                Accept: 'application/x.someline.v1+json'
            }
        },
        mounted(){
            console.log('Component Ready.');

        },
        watch: {},
        events: {},
        methods: {
            handleEdit(index, row) {
                console.log(index, row);
            },
            handleDelete(index, row) {
                console.log(index, row);
            },
            getSearchValue(val){
                console.log('getSearchValue: ', val);
                if (val && val.length > 0) {
                    return 'a:' + val + '';
                } else {
                    return '';
                }
            },
            onResourceResponse(response){
                console.log('response: ', response);
                console.log('response url: ', response.url);
            },
            onFormSubmit(){
                console.log('onFormSubmit');

                this.isLoading = true;

                var resource = this.$resource('wechats', {
//                    include: ''
                });

                resource.save({}, this.form_data)
                    .then((response) => {
                        console.log(response);

                        this.$message({
                            message: '保存成功',
                            type: 'success'
                        });

                        this.redirectToUrl('/console/wechats');

                    }, (response) => {
                        console.log(response);

                        var error_message = '保存失败';
                        try {
                            var response_error_message = response.data.message;
                            if (response_error_message) {
                                console.error(response_error_message);
                                error_message = this.$options.filters.truncate(response_error_message, 80);
                            }
                        } catch (e) {
                            console.error(e.stack);
                        }

                        this.$message({
                            message: error_message,
                            type: 'error'
                        });
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });

            },
            onEditorConfig(editor){
                console.log('onEditorConfig');

                // set editor
                this.editor = editor;

            },
            onEditorReady(editor){
                console.log('onEditorReady');

                this.handleEditorText(editor);
            },
            onEditorChange(editor){
                console.log('onEditorChange');

                this.handleEditorText(editor);
            },
            handleEditorText(editor){

                var text = editor.$txt.text();
                console.log('text: ', text);
//                this.form_data.example_editor_text = text;

                var formatText = editor.$txt.formatText();
                console.log('format text: ', formatText);
                this.form_data.body_text = formatText;

            }
        },
    }
</script>