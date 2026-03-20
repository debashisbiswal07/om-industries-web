import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PinAddEditComponent } from './pin-add-edit.component';
import { PageHeaderModule } from '../../../shared';
import { FormsModule } from '@angular/forms';
import { PinAddEditRoutingModule } from './pin-add-edit-routing.module';

@NgModule({
  declarations: [PinAddEditComponent],
  imports: [
    CommonModule, PageHeaderModule, FormsModule, PinAddEditRoutingModule
  ]
})
export class PinAddEditModule { }
