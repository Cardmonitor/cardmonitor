<template>

    <div>
        <article-show :item="show.item" :index="show.index" :counts="counts" @next="next($event)"></article-show>
        <article-table :cards="cards" :conditions="conditions" :model="model" :initial-items="items" :counts="counts" @toshow="toshow($event)"></article-table>
    </div>

</template>

<script>
    import articleShow from "./show.vue";
    import articleTable from "./table.vue";

    export default {
        components: {
            articleShow,
            articleTable,
        },

        props: {
            cards: {
                type: Object,
                required: true,
            },
            conditions: {
                type: Object,
                required: true,
            },
            model: {
                type: Object,
                required: true,
            },
        },

        computed: {
            groupedItems() {
                return _.groupBy(this.items, function(item) {
                    return item.state_key;
                });
            },
            counts() {
                return {
                    all: this.items.length,
                    ok: ((0 in this.groupedItems) ? this.groupedItems[0].length : 0),
                    open: ((-1 in this.groupedItems) ? this.groupedItems[-1].length : 0),
                    problem: ((1 in this.groupedItems) ? this.groupedItems[1].length : 0),
                };
            },
        },

        data() {
            return {
                items: this.model.line_items,
                show: {
                    item: null, // this.model.articles[0],
                    index: 0,
                },
            };
        },

        methods: {
            next(item) {
                Vue.set(this.items, this.show.index, item);
                this.show.index++;
                if (this.show.index in this.items === false) {
                    this.show.index = 0;
                }
                this.show.item = this.items[this.show.index];
            },
            toshow({index, item}) {
                this.show.index = index;
                this.show.item = item;
            },
        },
    };
</script>