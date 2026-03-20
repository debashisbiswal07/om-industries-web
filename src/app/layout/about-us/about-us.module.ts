import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AboutUsComponent } from './about-us.component';
import { AboutUsRoutingModule } from './about-us-routing.module';
import { PageHeaderModule } from './../../shared';

@NgModule({
  declarations: [AboutUsComponent],
  imports: [
    CommonModule, AboutUsRoutingModule, PageHeaderModule
  ]
})
export class AboutUsModule { }
