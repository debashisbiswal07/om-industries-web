import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { PageHeaderModule } from '../../shared';

import { GalleryRoutingModule } from './gallery-routing.module';
import { GalleryComponent } from './gallery.component';

@NgModule({
    imports: [CommonModule, GalleryRoutingModule, PageHeaderModule],
    declarations: [GalleryComponent]
})
export class GalleryModule { }
