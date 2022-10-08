<template>
    <div class="form-group">
        <input class="form-control form-control-sm" :class="{'is-invalid': cardmarket_comments in errors}" type="text" v-model="filter.cardmarket_comments" @keydown.enter="create(false)">
        <div class="invalid-feedback" v-text="'cardmarket_comments' in errors ? errors.cardmarket_comments[0] : ''"></div>
    </div>
</template>

<script>
    export default {

    };
</script>