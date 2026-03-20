import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeaderModule } from './../../shared';
import { FormsModule } from '@angular/forms';
import { AgGridModule } from 'ag-grid-angular';
import { PinComponent } from './pin.component';
import { PinRoutingModule } from './pin-routing.module';
import { PinRendererComponent } from './pin-renderer/pin-renderer.component';
import { PinImageRendererComponent } from './pin-image-renderer/pin-image-renderer.component';


@NgModule({
  declarations: [PinComponent, PinRendererComponent, PinImageRendererComponent],
  imports: [
    CommonModule, PageHeaderModule, PinRoutingModule, FormsModule, AgGridModule
  ]
})
export class PinModule { }
