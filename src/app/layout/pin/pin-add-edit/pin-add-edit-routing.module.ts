import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { PinAddEditComponent } from './pin-add-edit.component';

const routes: Routes = [
  {
      path: '',
      component: PinAddEditComponent
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PinAddEditRoutingModule {}
