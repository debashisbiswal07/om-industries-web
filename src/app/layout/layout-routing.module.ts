import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { LayoutComponent } from './layout.component';
import { AuthGuard } from '../shared/guard/auth.guard';

const routes: Routes = [
    {
        path: '',
        component: LayoutComponent,
        children: [
            { path: '', redirectTo: 'dashboard', pathMatch: 'prefix' },
            { path: 'dashboard', loadChildren: () => import('./dashboard/dashboard.module').then((m) => m.DashboardModule) },
            { path: 'about-us', loadChildren: () => import('./about-us/about-us.module').then((m) => m.AboutUsModule) },
            { path: 'products/:id', loadChildren: () => import('./product/product.module').then((m) => m.ProductModule) },
            { path: 'gallery', loadChildren: () => import('./gallery/gallery.module').then((m) => m.GalleryModule) },
            { path: 'enquiry', loadChildren: () => import('./enquiry/enquiry.module').then((m) => m.EnquiryModule) },            
            { path: 'contact-us', loadChildren: () => import('./contact-us/contact-us.module').then((m) => m.ContactUsModule) },
            { path: 'pin', loadChildren: () => import('./pin/pin.module').then((m) => m.PinModule), canActivate: [AuthGuard] },
            { path: 'add-edit-pin', loadChildren: () => import('./pin/pin-add-edit/pin-add-edit.module').then((m) => m.PinAddEditModule), canActivate: [AuthGuard] },
            { path: 'grid', loadChildren: () => import('./grid/grid.module').then((m) => m.GridModule), canActivate: [AuthGuard] },
            { path: 'blank-page', loadChildren: () => import('./blank-page/blank-page.module').then((m) => m.BlankPageModule) }
        ]
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class LayoutRoutingModule {}
