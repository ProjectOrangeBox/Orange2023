<form id="createForm">
  <h5>Create New Food</h5>
  <div class="mb-3">
    <label for="firstname" class="form-label">First Name</label>
    <input type="text" class="form-control" v-model="form.firstname">
  </div>
  <div class="mb-3">
    <label for="lastname" class="form-label">Last Name</label>
    <input type="text" class="form-control" v-model="form.lastname">
  </div>
  <div class="mb-3">
    <label for="age" class="form-label">Age</label>
    <input type="text" class="form-control" v-model="form.age">
  </div>
  <div class="mb-3 float-end">
    <a class="btn" v-on:click="closeModal">Cancel</a>
    <a v-on:click="saveModal" data-url="<?= getUrl('food-create-post') ?>" data-type="post" class="js-submit btn btn-primary">Submit</a>
  </div>
</form>