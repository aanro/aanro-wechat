<style scoped>
</style>

<template>

    <someline-table
            :order-by="orderBy"
            :sorted-by="sortedBy"
            :orderable-fields="orderableFields"
            :resource-path="resourcePath"
            :get-search-value="getSearchValue"
            @resource-response="onResourceResponse"
            @selection-change="handleSelectionChange"
    >
        <template slot="SomelineTableColumns">
            <el-table-column
                    type="selection"
                    width="55">
            </el-table-column>
            <el-table-column
                    width="60"
                    label="#">
                <template scope="scope">
                    {{ scope.row.someline_wechat_id }}
                </template>
            </el-table-column>
            <el-table-column
                    label="标题">
                <template scope="scope">
                    {{ scope.row.title }}
                </template>
            </el-table-column>
            <el-table-column
                    width="160"
                    label="更新于">
                <template scope="scope">
                    {{ scope.row.updated_at }}
                </template>
            </el-table-column>
            <el-table-column
                    width="100"
                    label="操作">
                <template scope="scope">
                    <button class="btn btn-default btn-sm r-2x"
                            @click="handleEdit(scope.$index, scope.row)">
                        <i class="fa fa-edit"></i>&nbsp;编辑
                    </button>
                </template>
            </el-table-column>
        </template>
    </someline-table>

</template>

<script>
    export default{
        props: [],
        data(){
            return {
                resourcePath: 'wechats',

                orderBy: 'someline_wechat_id',
                sortedBy: 'asc',

                orderableFields: [
                    {
                        name: 'someline_wechat_id',
                        display: '序号',
                    },
                    {
                        name: 'created_at',
                        display: '创建时间',
                    },
                    {
                        name: 'updated_at',
                        display: '更新时间',
                    },
                ],
            }
        },
        computed: {},
        components: {
//            'sl-user-list-item': require('./UserListGroupItem.vue'),
        },
        http: {
            root: '/api',
            headers: {
                Accept: 'application/x.someline.v1+json'
            }
        },
        mounted(){
            console.log('Component Ready.');

//            this.eventEmit('SomelineTable.doFetchData');
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
            handleSelectionChange(val){
                console.log('handleSelectionChange', val);
            },
        },
    }
</script>