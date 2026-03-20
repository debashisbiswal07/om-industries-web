import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PinAddEditComponent } from './pin-add-edit.component';

describe('PinAddEditComponent', () => {
  let component: PinAddEditComponent;
  let fixture: ComponentFixture<PinAddEditComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PinAddEditComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PinAddEditComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
